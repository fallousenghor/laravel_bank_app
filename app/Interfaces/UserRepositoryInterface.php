<?php

namespace App\Interfaces;

interface UserRepositoryInterface
{
    public function getAllUsers();
    public function getUserById($userId);
    /**
     * Get a user by telephone number or NCI (national identifier).
     * One of the two parameters should be provided.
     *
     * @param string|null $telephone
     * @param string|null $nci
     * @return \App\Models\User
     */
    public function getUserByTelOrNci($telephone = null, $nci = null);
    public function createUser(array $userDetails);
    public function updateUser($userId, array $userDetails);
    public function deleteUser($userId);
}
