<?php

namespace App\Interfaces;

interface CompteRepositoryInterface
{
    public function getAllComptes();
    public function getCompteById($compteId);
    public function createCompte(array $compteDetails);
    public function updateCompte($compteId, array $compteDetails);
    public function deleteCompte($compteId);
    public function getComptesByUserId($userId);
    // Récupère les comptes actifs de types Épargne ou Chèque
    public function getActiveComptes();
    // Récupère les comptes actifs de types Épargne ou Chèque pour un utilisateur
    public function getActiveComptesByUserId($userId);
}
