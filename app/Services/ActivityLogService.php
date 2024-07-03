<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /**
     * Log user authentication activities.
     */
    public static function logAuth(string $action, $user, array $context = [])
    {
        $logData = [
            'action' => $action,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('auth')->info($action, $logData);

        // Store in database using Spatie Activity Log
        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->withProperties($context)
                ->log($action);
        }
    }

    /**
     * Log order activities.
     */
    public static function logOrder(string $action, $order, $user = null, array $context = [])
    {
        $logData = [
            'action' => $action,
            'order_id' => $order ? $order->id : null,
            'order_number' => $order ? $order->order_number : null,
            'user_id' => $user ? $user->id : ($order ? $order->user_id : null),
            'total_amount' => $order ? $order->total_amount : null,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('orders')->info($action, $logData);

        // Store in database
        if ($order) {
            activity('orders')
                ->causedBy($user ?: $order->user)
                ->performedOn($order)
                ->withProperties($context)
                ->log($action);
        }
    }

    /**
     * Log payment activities.
     */
    public static function logPayment(string $action, $payment, $user = null, array $context = [])
    {
        $logData = [
            'action' => $action,
            'payment_id' => $payment ? $payment->id : null,
            'order_id' => $payment ? $payment->order_id : null,
            'amount' => $payment ? $payment->amount : null,
            'method' => $payment ? $payment->method : null,
            'status' => $payment ? $payment->status : null,
            'user_id' => $user ? $user->id : null,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('payments')->info($action, $logData);

        // Store in database
        if ($payment) {
            activity('payments')
                ->causedBy($user)
                ->performedOn($payment)
                ->withProperties($context)
                ->log($action);
        }
    }

    /**
     * Log product activities.
     */
    public static function logProduct(string $action, $product, $user = null, array $context = [])
    {
        $logData = [
            'action' => $action,
            'product_id' => $product ? $product->id : null,
            'product_name' => $product ? $product->name : null,
            'product_price' => $product ? $product->price : null,
            'user_id' => $user ? $user->id : null,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('products')->info($action, $logData);

        // Store in database
        if ($product) {
            activity('products')
                ->causedBy($user)
                ->performedOn($product)
                ->withProperties($context)
                ->log($action);
        }
    }

    /**
     * Log admin activities.
     */
    public static function logAdmin(string $action, $user, array $context = [])
    {
        $logData = [
            'action' => $action,
            'admin_id' => $user ? $user->id : null,
            'admin_email' => $user ? $user->email : null,
            'ip_address' => request()->ip(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('admin')->critical($action, $logData);

        // Store in database
        if ($user) {
            activity('admin')
                ->causedBy($user)
                ->withProperties($context)
                ->log($action);
        }
    }

    /**
     * Log security events.
     */
    public static function logSecurity(string $action, array $context = [])
    {
        $logData = [
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->warning($action, $logData);

        // Store critical security events in database
        activity('security')
            ->withProperties($logData)
            ->log($action);
    }

    /**
     * Log cart activities.
     */
    public static function logCart(string $action, $user, array $context = [])
    {
        $logData = [
            'action' => $action,
            'user_id' => $user ? $user->id : null,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('cart')->info($action, $logData);
    }
} 