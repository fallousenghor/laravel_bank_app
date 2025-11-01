<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the other user.
     */
    public function view(?User $user, User $model): bool
    {
        if ($user && $user->role === 'admin') {
            return true;
        }

        // Clients can only view their own information
        return $user && $user->id === $model->id;
    }

    /**
     * Determine whether the user can update the user model.
     */
    public function update(?User $user, User $model): bool
    {
        if ($user && $user->role === 'admin') {
            return true;
        }

        // Clients can only update their own information
        return $user && $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user model.
     */
    public function delete(?User $user, User $model): bool
    {
        return $user && $user->role === 'admin';
    }
}
