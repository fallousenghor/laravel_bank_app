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
    public function getAllComptes(array $filters = [], int $page = 1, int $limit = 10)
    {
        $query = Compte::query();

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulaire', 'ilike', '%' . $filters['search'] . '%')
                  ->orWhere('numero', 'ilike', '%' . $filters['search'] . '%');
            });
        }
        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Apply sorting - map API-friendly fields to actual DB columns
        $sort = $filters['sort'] ?? 'dateCreation';
        $order = $filters['order'] ?? 'desc';

        // Map allowed sort fields to DB columns
        $sortMap = [
            'dateCreation' => 'date_creation',
            'solde' => 'solde',
            'titulaire' => null, // handled via join below
        ];

        if (!array_key_exists($sort, $sortMap)) {
            $sort = 'dateCreation';
        }

        if ($sort === 'titulaire') {
            // Order by user prenom then nom
            $query->join('users', 'comptes.utilisateur_id', '=', 'users.id')
                  ->orderBy('users.prenom', $order)
                  ->orderBy('users.nom', $order)
                  ->select('comptes.*');
        } else {
            $column = $sortMap[$sort];
            $query->orderBy($column, $order);
        }

        // Apply pagination
        return $query->paginate($limit, ['*'], 'page', $page);
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
        // Use `client_id` as defined in migrations
        return Compte::where('client_id', $userId)->get();
    }

    public function getActiveComptes()
    {
        return Compte::whereIn('type', ['epargne', 'cheque'])
            ->where('statut', 'actif')
            ->get();
    }

    public function getActiveComptesByUserId($userId)
    {
        return Compte::where('client_id', $userId)
            ->whereIn('type', ['epargne', 'cheque'])
            ->where('statut', 'actif')
            ->get();
    }
}
