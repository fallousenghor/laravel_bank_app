<?php

namespace App\Interfaces;

interface CompteRepositoryInterface
{
    public function getAllComptes(array $filters = [], int $page = 1, int $limit = 10);
    public function getCompteById($compteId);
    public function createCompte(array $compteDetails);
    public function updateCompte($compteId, array $compteDetails);
    public function deleteCompte($compteId);
    public function getComptesByUserId($userId);
    // Récupère les comptes actifs de types Épargne ou Chèque
    public function getActiveComptes();
    // Récupère les comptes actifs de types Épargne ou Chèque pour un utilisateur
    public function getActiveComptesByUserId($userId);
    // Récupère un compte par son numéro unique
    public function getCompteByNumero(string $numero);
}
