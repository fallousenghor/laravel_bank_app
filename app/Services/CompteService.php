<?php

namespace App\Services;

use App\Interfaces\CompteRepositoryInterface;
use App\Models\Compte;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CompteService
{
    private $compteRepository;

    public function __construct(CompteRepositoryInterface $compteRepository)
    {
        $this->compteRepository = $compteRepository;
    }

    /**
     * Récupérer les comptes filtrés et paginés avec gestion des erreurs de soft delete
     */
    public function getFilteredComptesPaginated(array $filters = [], int $page = 1, int $limit = 10, $user = null)
    {
        try {
            $query = Compte::with('utilisateur');

            // Construction sécurisée de la requête avec des paramètres liés
            $query->where(function($q) {
                $q->where('type', '=', 'epargne')
                  ->orWhere('type', '=', 'cheque');
            })
            ->where('statut', '=', 'actif');

            // For Client, only their own comptes
            if ($user && $user->role !== 'admin') {
                $query->where('client_id', $user->id);
            }

            // Filtres supplémentaires
            if (isset($filters['type']) && in_array($filters['type'], ['epargne', 'cheque'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['statut']) && in_array($filters['statut'], ['actif', 'bloque', 'ferme'])) {
                $query->where('statut', $filters['statut']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('numero', 'like', "%{$search}%")
                      ->orWhereHas('utilisateur', function ($userQuery) use ($search) {
                          $userQuery->where('prenom', 'like', "%{$search}%")
                                    ->orWhere('nom', 'like', "%{$search}%");
                      });
                });
            }

            // Tri
            $sortField = $filters['sort'] ?? 'dateCreation';
            $sortOrder = $filters['order'] ?? 'desc';

            $allowedSortFields = ['dateCreation', 'solde', 'titulaire'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'dateCreation';
            }

            if ($sortField === 'dateCreation') {
                $query->orderBy('date_creation', $sortOrder);
            } elseif ($sortField === 'solde') {
                $query->orderBy('solde', $sortOrder);
            } elseif ($sortField === 'titulaire') {
                $query->join('users', 'comptes.utilisateur_id', '=', 'users.id')
                      ->orderBy('users.prenom', $sortOrder)
                      ->orderBy('users.nom', $sortOrder)
                      ->select('comptes.*');
            }

            // Pagination
            $limit = min($limit, 100);

            try {
                $comptes = $query->paginate($limit);
            } catch (\Exception $e) {
                // If the error is about deleted_at column not existing, try without soft deletes
                if (str_contains($e->getMessage(), 'deleted_at does not exist')) {
                    try {
                        // Create a new query without the global scopes
                        $queryWithoutScopes = Compte::with('utilisateur');

                        // Reapply all the filters manually
                        $queryWithoutScopes->whereIn('type', ['epargne', 'cheque'])
                                           ->where('statut', 'actif');

                        // For Client, only their own comptes
                        if ($user && $user->role !== 'admin') {
                            $queryWithoutScopes->where('utilisateur_id', $user->id);
                        }

                        // Filtres supplémentaires
                        if (isset($filters['type']) && in_array($filters['type'], ['epargne', 'cheque'])) {
                            $queryWithoutScopes->where('type', $filters['type']);
                        }

                        if (isset($filters['statut']) && in_array($filters['statut'], ['actif', 'bloque', 'ferme'])) {
                            $queryWithoutScopes->where('statut', $filters['statut']);
                        }

                        if (isset($filters['search'])) {
                            $search = $filters['search'];
                            $queryWithoutScopes->where(function ($q) use ($search) {
                                $q->where('numero', 'like', "%{$search}%")
                                  ->orWhereHas('utilisateur', function ($userQuery) use ($search) {
                                      $userQuery->where('prenom', 'like', "%{$search}%")
                                                ->orWhere('nom', 'like', "%{$search}%");
                                  });
                            });
                        }

                        // Tri
                        $sortField = $filters['sort'] ?? 'dateCreation';
                        $sortOrder = $filters['order'] ?? 'desc';

                        $allowedSortFields = ['dateCreation', 'solde', 'titulaire'];
                        if (!in_array($sortField, $allowedSortFields)) {
                            $sortField = 'dateCreation';
                        }

                        if ($sortField === 'dateCreation') {
                            $queryWithoutScopes->orderBy('date_creation', $sortOrder);
                        } elseif ($sortField === 'solde') {
                            $queryWithoutScopes->orderBy('solde', $sortOrder);
                        } elseif ($sortField === 'titulaire') {
                            $queryWithoutScopes->join('users', 'comptes.utilisateur_id', '=', 'users.id')
                                              ->orderBy('users.prenom', $sortOrder)
                                              ->orderBy('users.nom', $sortOrder)
                                              ->select('comptes.*');
                        }

                        $comptes = $queryWithoutScopes->paginate($limit);
                    } catch (\Exception $e2) {
                        Log::error('Erreur lors de la pagination des comptes (sans soft delete): ' . $e2->getMessage(), [
                            'trace' => $e2->getTraceAsString(),
                            'limit' => $limit
                        ]);
                        throw $e2;
                    }
                } else {
                    Log::error('Erreur lors de la pagination des comptes: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'query' => $query->toSql(),
                        'bindings' => $query->getBindings(),
                        'limit' => $limit
                    ]);
                    throw $e;
                }
            }

            return $comptes;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des comptes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
                'page' => $page,
                'limit' => $limit,
                'user_id' => $user ? $user->id : 'non défini'
            ]);

            throw $e;
        }
    }

    /**
     * Créer un compte avec gestion du client
     */
    public function createCompteWithClient(array $compteData, array $clientData = [])
    {
        try {
            $compte = Compte::createWithClient($compteData, $clientData);
            return $compte;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compteData' => $compteData,
                'clientData' => $clientData
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour les informations du client d'un compte
     */
    public function updateCompteClientInfo($compteId, array $updateData)
    {
        try {
            $compte = Compte::find($compteId);
            if (!$compte) {
                throw new \Exception("Compte non trouvé");
            }

            // Règle métier : n'autoriser le blocage QUE pour les comptes de type 'epargne'
            if (!isset($compte->type) || strtolower($compte->type) !== 'epargne') {
                throw new \Exception("Seul un compte d'épargne peut être bloqué via cette opération");
            }

            $compte->updateClientInfo($updateData);
            return $compte;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId,
                'updateData' => $updateData
            ]);
            throw $e;
        }
    }

    /**
     * Bloquer un compte
     */
    public function bloquerCompte($compteId, array $blockingData)
    {
        try {
            $compte = Compte::find($compteId);
            if (!$compte) {
                throw new \Exception("Compte non trouvé");
            }

            $compte->bloquer($blockingData);
            return $compte;
        } catch (\Exception $e) {
            Log::error('Erreur lors du blocage du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId,
                'blockingData' => $blockingData
            ]);
            throw $e;
        }
    }

    /**
     * Fermer un compte (soft delete)
     */
    public function fermerCompte($compteId)
    {
        try {
            $compte = $this->compteRepository->getCompteById($compteId);
            if (!$compte) {
                throw new \Exception("Compte non trouvé");
            }

            $compte->fermer();
            return $compte;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la fermeture du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId
            ]);
            throw $e;
        }
    }
}
