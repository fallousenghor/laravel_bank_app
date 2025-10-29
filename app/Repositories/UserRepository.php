<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserById($userId)
    {
        return User::findOrFail($userId);
    }

    /**
     * Get a user by telephone number or NCI (national identifier).
     * If both are provided, telephone match takes precedence.
     *
     * @param string|null $telephone
     * @param string|null $nci
     * @return \App\Models\User
     */
    public function getUserByTelOrNci($telephone = null, $nci = null)
    {
        $query = User::query();

        if ($telephone) {
            $query->where('telephone', $telephone);
            if ($nci) {
                // If both provided, prefer exact telephone match first, but allow fallback
                $result = $query->first();
                if ($result) {
                    return $result;
                }
            }
            return $query->firstOrFail();
        }

        if ($nci) {
            return User::where('nci', $nci)->firstOrFail();
        }

        throw new \InvalidArgumentException('telephone or nci must be provided');
    }

    public function createUser(array $userDetails)
    {
        return User::create($userDetails);
    }

    public function updateUser($userId, array $userDetails)
    {
        return User::whereId($userId)->update($userDetails);
    }

    public function deleteUser($userId)
    {
        return User::destroy($userId);
    }
}
