<?php

namespace App\Repositories;

use App\Interfaces\CompteRepositoryInterface;
use App\Models\Compte;

class CompteRepository implements CompteRepositoryInterface
{
    public function getAllComptes()
    {
        return Compte::all();
    }

    public function getCompteById($compteId)
    {
        return Compte::findOrFail($compteId);
    }

    public function createCompte(array $compteDetails)
    {
        return Compte::create($compteDetails);
    }

    public function updateCompte($compteId, array $compteDetails)
    {
        return Compte::whereId($compteId)->update($compteDetails);
    }

    public function deleteCompte($compteId)
    {
        return Compte::destroy($compteId);
    }

    public function getComptesByUserId($userId)
    {
        // La colonne dans la table comptes est `utilisateur_id`
        return Compte::where('utilisateur_id', $userId)->get();
    }

    public function getActiveComptes()
    {
        return Compte::whereIn('type', ['Épargne', 'Chèque'])
            ->where('statut', 'Actif')
            ->get();
    }

    public function getActiveComptesByUserId($userId)
    {
        return Compte::where('utilisateur_id', $userId)
            ->whereIn('type', ['Épargne', 'Chèque'])
            ->where('statut', 'Actif')
            ->get();
    }
}
