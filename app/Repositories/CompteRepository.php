<?php

namespace App\Repositories;

use App\Interfaces\CompteRepositoryInterface;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

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
        \DB::beginTransaction();
        try {
            // Vérifier si l'utilisateur existe, sinon le créer
            $user = \App\Models\User::where('name', $compteDetails['titulaire'])->first();

            if (!$user) {
                // Créer un nouvel utilisateur
                $user = \App\Models\User::create([
                    'name' => $compteDetails['titulaire'],
                    'email' => $compteDetails['email'] ?? null,
                    'password' => \Hash::make(\Str::random(10)) // Mot de passe temporaire
                ]);

                // Déclencher l'événement de création de client
                event(new \App\Events\ClientCreated($user, $user->password, \Str::random(6)));
            }

            // Ajouter l'ID de l'utilisateur aux détails du compte
            $compteDetails['utilisateur_id'] = $user->id;

            // Créer le compte
            $compte = Compte::create($compteDetails);

            // Déclencher l'événement de création de compte
            event(new \App\Events\CompteCreated($compte));

            \DB::commit();
            return $compte;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Erreur lors de la création du compte: ' . $e->getMessage());
            throw $e;
        }
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
        return Compte::whereIn('type', ['epargne', 'cheque'])
            ->where('statut', 'actif')
            ->get();
    }

    public function getActiveComptesByUserId($userId)
    {
        return Compte::where('utilisateur_id', $userId)
            ->whereIn('type', ['epargne', 'cheque'])
            ->where('statut', 'actif')
            ->get();
    }
}
