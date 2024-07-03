<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get dashboard statistics.
     */
    public function dashboard()
    {
        try {
            Log::info('Starting dashboard method');
            
            try {
                $this->authorize('viewAny', Order::class);
                Log::info('Authorization check passed');
            } catch (\Exception $e) {
                Log::error('Authorization failed: ' . $e->getMessage());
                throw $e;
            }

            // Total revenue
            try {
                $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount') ?? 0;
                Log::info('Total revenue calculated: ' . $totalRevenue);
            } catch (\Exception $e) {
                Log::error('Error calculating total revenue: ' . $e->getMessage());
                $totalRevenue = 0;
            }

            // Total orders
            try {
                $totalOrders = Order::count() ?? 0;
                Log::info('Total orders counted: ' . $totalOrders);
            } catch (\Exception $e) {
                Log::error('Error counting orders: ' . $e->getMessage());
                $totalOrders = 0;
            }

            // Total products
            try {
                $totalProducts = Product::count() ?? 0;
                Log::info('Total products counted: ' . $totalProducts);
            } catch (\Exception $e) {
                Log::error('Error counting products: ' . $e->getMessage());
                $totalProducts = 0;
            }

            // Total users
            try {
                $totalUsers = User::count() ?? 0;
                Log::info('Total users counted: ' . $totalUsers);
            } catch (\Exception $e) {
                Log::error('Error counting users: ' . $e->getMessage());
                $totalUsers = 0;
            }

            // Recent orders with error handling
            try {
                $recentOrders = Order::with(['user', 'items.product'])
                    ->latest()
                    ->take(10)
                    ->get();
                Log::info('Recent orders fetched: ' . count($recentOrders));
            } catch (\Exception $e) {
                Log::error('Error fetching recent orders: ' . $e->getMessage());
                $recentOrders = [];
            }

            // Top selling products with error handling
            try {
                if (Schema::hasTable('order_items')) {
                    Log::info('order_items table exists, fetching top products');
                    $topProducts = DB::table('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->select(
                            'products.id',
                            'products.name',
                            'products.price',
                            DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'),
                            DB::raw('COALESCE(SUM(order_items.total), 0) as revenue')
                        )
                        ->groupBy('products.id', 'products.name', 'products.price')
                        ->orderBy('total_sold', 'desc')
                        ->take(5)
                        ->get();
                } else {
                    Log::warning('order_items table does not exist, using fallback');
                    $topProducts = Product::select('id', 'name', 'price')
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(function ($product) {
                            $product->total_sold = 0;
                            $product->revenue = 0;
                            return $product;
                        });
                }
                Log::info('Top products fetched: ' . count($topProducts));
            } catch (\Exception $e) {
                Log::error('Error fetching top products: ' . $e->getMessage());
                $topProducts = [];
            }

            // Orders by status with error handling
            try {
                $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get();
                Log::info('Orders by status fetched: ' . count($ordersByStatus));
            } catch (\Exception $e) {
                Log::error('Error fetching orders by status: ' . $e->getMessage());
                $ordersByStatus = [];
            }

            // Monthly revenue with error handling
            try {
                $monthlyRevenue = Order::where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->select(
                        DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                        DB::raw('COALESCE(SUM(total_amount), 0) as revenue')
                    )
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
                Log::info('Monthly revenue fetched: ' . count($monthlyRevenue));
            } catch (\Exception $e) {
                Log::error('Error fetching monthly revenue: ' . $e->getMessage());
                $monthlyRevenue = [];
            }

            Log::info('All dashboard data collected successfully');
            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $totalOrders,
                    'total_products' => $totalProducts,
                    'total_users' => $totalUsers,
                    'recent_orders' => $recentOrders,
                    'top_products' => $topProducts,
                    'orders_by_status' => $ordersByStatus,
                    'monthly_revenue' => $monthlyRevenue,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du chargement du tableau de bord',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users (Admin only).
     */
    public function users(Request $request)
    {
        try {
            Log::info('Admin users request', [
                'user' => $request->user()->toArray(),
                'roles' => $request->user()->roles->pluck('name'),
                'is_admin' => $request->user()->hasRole('admin')
            ]);
            
            $this->authorize('viewAny', User::class);
            
            $query = User::with('roles');

            // Search by name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->has('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            $perPage = $request->get('limit', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Admin users error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des utilisateurs',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Auth\Access\AuthorizationException ? 403 : 500);
        }
    }

    /**
     * Get all orders (Admin only).
     */
    public function orders(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'items.product']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search by order number or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('limit', 15);
        $orders = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Show details of a specific user (Admin only).
     */
    public function showUser(User $user)
    {
        $this->authorize('view', $user);
        return response()->json([
            'success' => true,
            'data' => $user->load('roles')
        ]);
    }

    /**
     * Update a specific user (Admin only).
     */
    public function updateUser(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $validator = \Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|string|exists:roles,name',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->except(['password', 'password_confirmation', 'role']);
        if ($request->filled('password')) {
            $data['password'] = \Hash::make($request->password);
        }
        $user->update($data);
        // Gérer le rôle si fourni
        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh()->load('roles')
        ]);
    }

    /**
     * Delete a specific user (Admin only).
     */
    public function deleteUser(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get orders of a specific user (Admin only).
     */
    public function userOrders(User $user, Request $request)
    {
        $this->authorize('view', $user);
        $orders = $user->orders()->with(['items.product'])->latest()->paginate($request->get('limit', 15));
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
} 