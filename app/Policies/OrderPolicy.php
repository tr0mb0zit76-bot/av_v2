<?php
// app/Policies/OrderPolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Все авторизованные могут видеть список
    }
    
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || 
               $user->isSupervisor() || 
               $order->manager_id === $user->id;
    }
    
    public function create(User $user): bool
    {
        return $user->isManager() || $user->isAdmin() || $user->isSupervisor();
    }
    
    public function update(User $user, Order $order): bool
    {
        if ($order->edit_locked) {
            return false;
        }
        
        return $user->isAdmin() || 
               $user->isSupervisor() || 
               $order->manager_id === $user->id;
    }
    
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin() || 
               ($user->isManager() && $order->manager_id === $user->id);
    }
}