<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Compte;

class ComptePolicy
{
    /**
     * Determine whether the user can view the compte.
     */
    public function view(?User $user, Compte $compte): bool
    {
        if ($user && ($user->role === 'admin')) {
            return true;
        }

        // Clients can only view their own accounts
        if ($user && $user->id === $compte->utilisateur_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the compte (or its owner data).
     */
    public function update(?User $user, Compte $compte): bool
    {
        if ($user && ($user->role === 'admin')) {
            return true;
        }

        // Clients can only update their own accounts
        return $user && $user->id === $compte->utilisateur_id;
    }

    /**
     * Determine whether the user can delete the compte.
     */
    public function delete(?User $user, Compte $compte): bool
    {
        if ($user && ($user->role === 'admin')) {
            return true;
        }

        // Clients can only delete their own accounts
        return $user && $user->id === $compte->utilisateur_id;
    }
}
