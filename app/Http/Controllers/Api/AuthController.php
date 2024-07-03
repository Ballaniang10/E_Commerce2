<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Services\ActivityLogService;
use Google\Client as GoogleClient;
use Facebook\Facebook;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);

        // Assign client role
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $user->assignRole($clientRole);

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLogService::logAuth('User registered', $user, [
            'registration_ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token
            ]
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            ActivityLogService::logSecurity('Failed login attempt', [
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load roles and permissions
        $user->load(['roles', 'roles.permissions']);
        $user->isAdmin = $user->hasRole('admin');

        ActivityLogService::logAuth('User logged in', $user, [
            'login_ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        ActivityLogService::logAuth('User logged out', $user);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'roles.permissions']);
        $user->isAdmin = $user->hasRole('admin');

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * GET /api/auth/profile (alias pour /api/auth/user)
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'roles.permissions']);
        $user->isAdmin = $user->hasRole('admin');

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * PUT /api/auth/change-password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string'
        ]);
        
        // Vérifier l'ancien mot de passe
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 400);
        }
        
        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        
        return response()->json(['success' => true, 'message' => 'Password updated successfully']);
    }

    /**
     * Update authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['password', 'password_confirmation']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()->load('roles')
        ]);
    }

    public function socialLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'provider' => 'required|string|in:google,facebook',
                'email' => 'required|email',
                'name' => 'required|string',
                'photo' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier le token en fonction du provider
            $verified = false;
            $userData = null;

            if ($request->provider === 'google') {
                $client = new GoogleClient(['client_id' => config('services.google.client_id')]);
                $payload = $client->verifyIdToken($request->token);
                
                if ($payload) {
                    $verified = true;
                    $userData = [
                        'email' => $payload['email'],
                        'name' => $payload['name'],
                        'photo' => $payload['picture'] ?? null
                    ];
                }
            } else {
                $fb = new Facebook([
                    'app_id' => config('services.facebook.client_id'),
                    'app_secret' => config('services.facebook.client_secret'),
                    'default_graph_version' => 'v12.0',
                ]);

                try {
                    $response = $fb->get('/me?fields=id,name,email', $request->token);
                    $fbUser = $response->getGraphUser();
                    
                    $verified = true;
                    $userData = [
                        'email' => $fbUser->getEmail(),
                        'name' => $fbUser->getName(),
                        'photo' => $request->photo
                    ];
                } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                    return response()->json(['message' => 'Graph returned an error: ' . $e->getMessage()], 401);
                } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                    return response()->json(['message' => 'Facebook SDK returned an error: ' . $e->getMessage()], 401);
                }
            }

            if (!$verified) {
                return response()->json(['message' => 'Invalid token'], 401);
            }

            // Chercher ou créer l'utilisateur
            $user = User::where('email', $userData['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make(str_random(16)),
                    'profile_photo_url' => $userData['photo'],
                    'email_verified_at' => now()
                ]);
            }

            // Créer le token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }
} 