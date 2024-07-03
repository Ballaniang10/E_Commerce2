<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('client');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('admin') || $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('client') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admin can update orders, or owner can update specific fields
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Clients can only update their own orders if status is pending
        return $order->user_id === $user->id && $order->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admin can delete orders, or owner can cancel if pending
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $order->user_id === $user->id && $order->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update order status.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }
} 