<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Compte;

class ComptePolicy
{
    /**
     * Determine whether the user can view the compte.
     */
    public function view(User $user, Compte $compte): bool
    {
        if (($user->role ?? '') === 'admin') {
            return true;
        }

        return ($compte->client_id ?? null) === $user->id;
    }

    /**
     * Determine whether the user can update the compte (and its client info).
     */
    public function update(User $user, Compte $compte): bool
    {
        if (($user->role ?? '') === 'admin') {
            return true;
        }

        return ($compte->client_id ?? null) === $user->id;
    }

    /**
     * Determine whether the user can delete the compte.
     */
    public function delete(User $user, Compte $compte): bool
    {
        // Only admin can delete
        return (($user->role ?? '') === 'admin');
    }

    /**
     * Determine whether the user can block the compte.
     */
    public function block(User $user, Compte $compte): bool
    {
        // Only admin can block comptes
        return (($user->role ?? '') === 'admin');
    }

    /**
     * Determine whether the user can create a compte.
     *
     * For creation we allow any authenticated user; controller will enforce
     * stricter rules (e.g., non-admins can only create for themselves).
     */
    public function create(User $user): bool
    {
        return !empty($user->id);
    }
}
