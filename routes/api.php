<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\TestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/social-login', [AuthController::class, 'socialLogin']);

// Products (public) - Routes spécifiques AVANT les routes avec paramètres
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::post('/products/by-ids', [ProductController::class, 'byIds']);
Route::post('/products/by-slugs', [ProductController::class, 'bySlugs']);  // Nouvelle route
Route::get('/products/filter', [ProductController::class, 'filter']);
Route::get('/products/price-ranges', [ProductController::class, 'priceRanges']);
Route::get('/products/category/{categoryId}', [ProductController::class, 'byCategory']);
Route::get('/products/{product}/similar', [ProductController::class, 'similar']);  // Utiliser le modèle binding
Route::get('/products/{product}', [ProductController::class, 'show']);

// Categories (public) - Routes spécifiques AVANT les routes avec paramètres
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/main', [CategoryController::class, 'main']);
Route::get('/categories/{id}/children', [CategoryController::class, 'children']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Test route
Route::get('/test', [TestController::class, 'test']);

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'api.rate.limit:100,1'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/user', [AuthController::class, 'updateProfile']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::put('/auth/update-profile', [AuthController::class, 'updateProfile']);

    // Orders - Routes spécifiques AVANT les routes avec paramètres
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/user', [OrderController::class, 'userOrders']);
    Route::get('/orders/track/{trackingNumber}', [OrderController::class, 'track']);
    Route::post('/orders/check-availability', [OrderController::class, 'checkAvailability']);
    Route::post('/orders/calculate-shipping', [OrderController::class, 'calculateShipping']);
    Route::post('/orders/apply-promo', [OrderController::class, 'applyPromo']);
    Route::get('/orders/stats', [OrderController::class, 'stats']); // Stats route BEFORE parameterized routes
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'downloadInvoice']);
    Route::put('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::put('/orders/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery']);
    Route::get('/orders/{order}/history', [OrderController::class, 'history']);
    Route::post('/orders/{order}/return', [OrderController::class, 'return']);
    Route::post('/orders/{order}/rate', [OrderController::class, 'rate']);
    Route::get('/orders/{order}/payment-status', [OrderController::class, 'paymentStatus']);
    Route::post('/orders/{order}/payment', [OrderController::class, 'payment']);

    // Payments (stricter rate limiting)
    Route::middleware('api.rate.limit:10,1')->group(function () {
        Route::post('/payments/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment']);
        Route::post('/payments/cash-on-delivery', [PaymentController::class, 'cashOnDelivery']);
    });

    // Cart (panier)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        // Products management
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Categories management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Orders management
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);

        // Users management
        Route::get('/admin/users/{user}', [AdminController::class, 'showUser']);
        Route::put('/admin/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser']);
        Route::get('/admin/users/{user}/orders', [AdminController::class, 'userOrders']);

        // Dashboard
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::get('/admin/orders', [AdminController::class, 'orders']);
    });
}); 