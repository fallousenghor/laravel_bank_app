<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListComptesRequest;
use App\Http\Requests\ShowCompteRequest;
use App\Http\Requests\MineComptesRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Requests\BloquerCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Models\User;
use App\Events\ClientCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use OpenApi\Annotations as OA;
use App\Interfaces\CompteRepositoryInterface;
use App\Traits\ApiResponse;

/**
 * @OA\Info(
 *     title="API de Gestion des Comptes Bancaires",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 * (Servers are generated from configuration (APP_URL / SWAGGER_BASE_URL) so they are set per-environment.)
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class CompteController extends Controller
{
    use ApiResponse;
    private $compteRepository;

    public function __construct(CompteRepositoryInterface $compteRepository)
    {
        $this->compteRepository = $compteRepository;
    }

    /**
    * @OA\Delete(
    *     path="/api/v1/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Supprimer un compte",
     *     description="Effectue une suppression douce (soft delete) d'un compte",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du compte à supprimer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="statut", type="string", example="ferme"),
     *                 @OA\Property(property="dateFermeture", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Non autorisé"
     *     )
     * )
     */
    public function destroy($id)
    {
        $compte = $this->compteRepository->getCompteById($id);

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Mise à jour du statut à "ferme" avant la suppression
        $compte->statut = 'ferme';
        $compte->save();

    // NOTE: Archive job removed — we only mark the compte as closed here.
    // Archiving/deletion background job was causing side-effects; to avoid impacting
    // API actions we do not dispatch it anymore.

        return $this->successResponse([
            'id' => $compte->id,
            'numeroCompte' => $compte->numero,
            'statut' => $compte->statut,
            'dateFermeture' => now()
        ], 'Compte fermé avec succès');
    }

    /**
    * @OA\Get(
    *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes",
     *     description="Retourne la liste paginée de tous les comptes bancaires non supprimés",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="admin_id",
     *         in="query",
     *         description="ID de l'admin (UUID, pour accès temporaire sans authentification)",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"Épargne", "Chèque"}, example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", enum={"Actif", "Bloqué", "Fermé"}, example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true, example="Inactivité de 30+ jours"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="totalPages", type="integer", example=3),
     *                 @OA\Property(property="totalItems", type="integer", example=25),
     *                 @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                 @OA\Property(property="hasNext", type="boolean", example=true),
     *                 @OA\Property(property="hasPrevious", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="links", type="object",
    *                 @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
    *                 @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
    *                 @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
    *                 @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié ou ID administrateur requis",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentification requise ou paramètre admin_id")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des paramètres",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="L'ID admin doit être un UUID valide."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="admin_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="L'ID admin doit être un UUID valide.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(ListComptesRequest $request)
    {
        try {
            // Get validated data
            $validated = $request->validated();

            // Setup filters
            $filters = [
                'type' => $validated['type'] ?? null,
                'statut' => $validated['statut'] ?? null,
                'search' => $validated['search'] ?? null,
                'sort' => $validated['sort'] ?? 'dateCreation',
                'order' => $validated['order'] ?? 'desc'
            ];

            // Get paginated results
            $user = $request->user();
            $adminId = $validated['admin_id'] ?? null;

            // Validate authentication or admin access
            if (!$user && !$adminId) {
                return $this->errorResponse('Authentification requise ou paramètre admin_id', 401);
            }

            // If admin_id is provided, verify the user is an admin
            if ($adminId) {
                $admin = \App\Models\User::find($adminId);
                if (!$admin || $admin->role !== 'admin') {
                    return $this->errorResponse('Accès non autorisé', 403);
                }
            }

            // Get paginated results with filters
            $comptes = $this->compteRepository->getAllComptes(
                $filters,
                $validated['page'] ?? 1,
                $validated['limit'] ?? 10
            );

            // If authenticated user is not an admin, filter results to show only their accounts
            if ($user && $user->role !== 'admin') {
                $filters['client_id'] = $user->id;
                $comptes = $this->compteRepository->getAllComptes(
                    $filters,
                    $validated['page'] ?? 1,
                    $validated['limit'] ?? 10
                );
            }
            $adminId = $request->validated()['admin_id'] ?? null;
            if (!$user && !$adminId) {
                return $this->errorResponse("Authentification requise ou paramètre admin_id", 401);
            }

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
            if ($request->has('type') && in_array($request->type, ['epargne', 'cheque'])) {
                $query->where('type', $request->type);
            }

            if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque', 'ferme'])) {
                $query->where('statut', $request->statut);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('numero', 'like', "%{$search}%")
                      ->orWhereHas('utilisateur', function ($userQuery) use ($search) {
                          $userQuery->where('prenom', 'like', "%{$search}%")
                                    ->orWhere('nom', 'like', "%{$search}%");
                      });
                });
            }

            // Tri
            $sortField = $request->get('sort', 'dateCreation');
            $sortOrder = $request->get('order', 'desc');

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
            $limit = min($request->get('limit', 10), 100);

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
                        if ($request->has('type') && in_array($request->type, ['epargne', 'cheque'])) {
                            $queryWithoutScopes->where('type', $request->type);
                        }

                        if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque', 'ferme'])) {
                            $queryWithoutScopes->where('statut', $request->statut);
                        }

                        if ($request->has('search')) {
                            $search = $request->search;
                            $queryWithoutScopes->where(function ($q) use ($search) {
                                $q->where('numero', 'like', "%{$search}%")
                                  ->orWhereHas('utilisateur', function ($userQuery) use ($search) {
                                      $userQuery->where('prenom', 'like', "%{$search}%")
                                                ->orWhere('nom', 'like', "%{$search}%");
                                  });
                            });
                        }

                        // Tri
                        $sortField = $request->get('sort', 'dateCreation');
                        $sortOrder = $request->get('order', 'desc');

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
                        \Log::error('Erreur lors de la pagination des comptes (sans soft delete): ' . $e2->getMessage(), [
                            'trace' => $e2->getTraceAsString(),
                            'limit' => $limit
                        ]);
                        throw $e2;
                    }
                } else {
                    \Log::error('Erreur lors de la pagination des comptes: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'query' => $query->toSql(),
                        'bindings' => $query->getBindings(),
                        'limit' => $limit
                    ]);
                    throw $e;
                }
            }

            return $this->paginatedResponse($comptes, 'Comptes récupérés');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'admin_id' => $adminId ?? 'non défini',
                'user' => $user ? ['id' => $user->id, 'role' => $user->role] : 'non authentifié'
            ]);

            return $this->errorResponse("Erreur interne du serveur - " . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
    *     path="/api/v1/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Obtenir les détails d'un compte spécifique",
     *     description="Retourne les détails d'un compte bancaire spécifique avec son utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du compte (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="a02eab71-0ae7-48cf-bc37-92d0493737e1"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CPT123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"Épargne", "Chèque"}, example="Épargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1000.50),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="statut", type="string", enum={"Actif", "Bloqué", "Fermé"}, example="Actif"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-10-23T00:00:00Z"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Détails du compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="ID administrateur requis",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="ID administrateur requis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function show(ShowCompteRequest $request, $id)
    {
        // L'ID est déjà validé par ShowCompteRequest
        $compte = $this->compteRepository->getCompteById($id);
        return $this->successResponse(new CompteResource($compte), 'Détails du compte');
    }

    /**
     * @OA\Get(
    *     path="/api/v1/comptes/mine",
     *     tags={"Comptes"},
     *     summary="Obtenir les comptes du client connecté",
     *     description="Retourne la liste des comptes actifs du client authentifié ou via user_id en paramètre",
     *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="user_id",
    *         in="query",
    *         description="ID de l'utilisateur (UUID) - requis si non authentifié",
    *         required=false,
    *         @OA\Schema(type="string", format="uuid", example="a037c752-44b6-489f-8502-ae011d0e0793")
    *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes de l'utilisateur récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"Épargne", "Chèque"}, example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", enum={"Actif", "Bloqué", "Fermé"}, example="actif"),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true, example=null),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )),
     *             @OA\Property(property="message", type="string", example="Comptes de l'utilisateur")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre user_id requis lorsque non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paramètre 'user_id' requis lorsque non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function mine(MineComptesRequest $request)
    {
        try {
            // When unauthenticated, allow passing `user_id` as a query parameter for testing.
            $user = $request->user();
            $userId = $user?->id ?? $request->validated()['user_id'] ?? null;

            if (!$userId) {
                return $this->errorResponse("Paramètre 'user_id' requis lorsque non authentifié", 400);
            }

            // Verify if user exists
            $userExists = \App\Models\User::where('id', $userId)->exists();
            if (!$userExists) {
                return $this->errorResponse("Utilisateur non trouvé", 404);
            }

            $comptes = $this->compteRepository->getActiveComptesByUserId($userId);
            return $this->successResponse(CompteResource::collection($comptes), 'Comptes de l\'utilisateur');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes utilisateur: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? 'non défini',
                'request' => $request->all()
            ]);
            return $this->errorResponse("Erreur interne du serveur", 500);
        }
    }

    /**
     * @OA\Post(
    *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire pour un client existant ou nouveau",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "solde", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *             @OA\Property(property="solde", type="number", format="float", minimum=10000, example=50000),
     *             @OA\Property(property="devise", type="string", default="FCFA", example="FCFA"),
     *             @OA\Property(property="client", type="object",
     *                 required={"titulaire", "email", "telephone", "adresse"},
     *                 @OA\Property(property="id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="nci", type="string", nullable=true, example="1234567890123"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@email.com"),
     *                 @OA\Property(property="telephone", type="string", example="771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numero", type="string", example="CPT123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="cheque"),
     *                 @OA\Property(property="solde", type="number", format="float", example=50000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="statut", type="string", example="Actif"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-10-26T10:00:00Z"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nom", type="string", example="Diallo"),
     *                     @OA\Property(property="prenom", type="string", example="Amadou"),
     *                     @OA\Property(property="email", type="string", example="amadou.diallo@email.com"),
     *                     @OA\Property(property="telephone", type="string", example="771234567")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreCompteRequest $request)
    {
        try {
            $validated = $request->validated();

            // Find existing client by id, email or telephone (in that order)
            $client = null;
            $clientInput = $validated['client'] ?? [];

            if (!empty($clientInput['id'])) {
                $client = User::find($clientInput['id']);
            }

            if (!$client && !empty($clientInput['email'])) {
                $client = User::where('email', $clientInput['email'])->first();
            }

            if (!$client && !empty($clientInput['telephone'])) {
                $client = User::where('telephone', $clientInput['telephone'])->first();
            }

            if (!$client) {
                // Create new client and send credentials + verification code
                $password = Str::random(8);
                // Use a 6-digit numeric code for SMS verification
                $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

                // Parse titulaire into prenom / nom more robustly
                $titulaire = $clientInput['titulaire'] ?? '';
                $parts = preg_split('/\s+/', trim($titulaire));
                $prenom = $parts[0] ?? '';
                $nom = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ($parts[0] ?? '');

                $client = User::create([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $clientInput['email'] ?? null,
                    'telephone' => $clientInput['telephone'] ?? null,
                    'adresse' => $clientInput['adresse'] ?? null,
                    'nci' => $clientInput['nci'] ?? null,
                    'code' => $code,
                    'password' => Hash::make($password),
                    // DB uses 'user' for client role (enum: 'admin','user')
                    'role' => 'user',
                ]);

                // Fire event for notifications (email + SMS)
                event(new ClientCreated($client, $password, $code));
            }

            // Create account — only include 'devise' if the DB column exists (migrations may be out of sync)
            $compteData = [
                'type' => $validated['type'],
                'solde' => $validated['solde'],
                'statut' => 'actif',
                'date_creation' => now(),
                // DB column is client_id (UUID foreign key)
                'client_id' => $client->id,
            ];

            if (Schema::hasColumn('comptes', 'devise')) {
                $compteData['devise'] = $validated['devise'] ?? 'FCFA';
            }

            $compte = Compte::create($compteData);

            // Load the client relationship
            $compte->load('utilisateur');

            return $this->successResponse(
                new CompteResource($compte),
                'Compte créé avec succès',
                201
            );

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse("Erreur lors de la création du compte", 500);
        }
    }

    /**
     * @OA\Patch(
    *     path="/api/v1/comptes/{compteId}",
     *     tags={"Comptes"},
     *     summary="Mettre à jour les informations du client",
     *     description="Modifie les informations du client associé à un compte bancaire. Tous les champs sont optionnels mais au moins un champ doit être fourni.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *             @OA\Property(property="informationsClient", type="object",
     *                 @OA\Property(property="telephone", type="string", example="+221771234568"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *                 @OA\Property(property="password", type="string", example="newpassword123"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations du client mises à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *                 @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="bloque"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T11:00:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou aucun champ fourni",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Au moins un champ de modification doit être fourni.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(UpdateCompteRequest $request, $compteId)
    {
        try {
            $validated = $request->validated();

            // Find the compte
            $compte = Compte::find($compteId);
            if (!$compte) {
                return $this->errorResponse("Compte non trouvé", 404);
            }

            // Règle métier : n'autoriser le blocage QUE pour les comptes de type 'epargne'
            // Si le compte n'est pas de type 'epargne' (par ex. 'cheque'), refuser l'opération
            if (!isset($compte->type) || strtolower($compte->type) !== 'epargne') {
                return $this->errorResponse("Seul un compte d'épargne peut être bloqué via cette opération", 422);
            }

            // Get the associated user
            $user = $compte->utilisateur;
            if (!$user) {
                return $this->errorResponse("Utilisateur associé non trouvé", 404);
            }

            \DB::beginTransaction();

            // Update titulaire if provided
            if (isset($validated['titulaire'])) {
                // Parse titulaire into prenom / nom
                $titulaire = $validated['titulaire'];
                $parts = preg_split('/\s+/', trim($titulaire));
                $prenom = $parts[0] ?? '';
                $nom = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ($parts[0] ?? '');

                $user->prenom = $prenom;
                $user->nom = $nom;
            }

            // Update client information if provided
            if (isset($validated['informationsClient'])) {
                $clientInfo = $validated['informationsClient'];

                if (isset($clientInfo['telephone'])) {
                    $user->telephone = $clientInfo['telephone'];
                }

                if (isset($clientInfo['email'])) {
                    $user->email = $clientInfo['email'];
                }

                if (isset($clientInfo['password'])) {
                    $user->password = Hash::make($clientInfo['password']);
                }

                if (isset($clientInfo['nci'])) {
                    $user->nci = $clientInfo['nci'];
                }
            }

            // Save user changes
            $user->save();

            \DB::commit();

            // Load the updated relationship
            $compte->load('utilisateur');

            return $this->successResponse(
                new CompteResource($compte),
                'Compte mis à jour avec succès'
            );

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId,
                'request' => $request->all()
            ]);

            return $this->errorResponse("Erreur lors de la mise à jour du compte", 500);
        }
    }

    /**
     * @OA\Post(
    *     path="/api/v1/comptes/{compteId}/bloquer",
     *     tags={"Comptes"},
     *     summary="Bloquer un compte bancaire",
     *     description="Bloque un compte bancaire avec des dates de début et fin de blocage",
     *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="admin_id",
    *         in="query",
    *         description="ID de l'admin (pour accès temporaire sans authentification)",
    *         required=false,
    *         @OA\Schema(type="string", format="uuid")
    *     ),
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte à bloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date_debut_blocage", "date_fin_blocage"},
     *             @OA\Property(property="date_debut_blocage", type="string", format="date-time", example="2023-12-01T00:00:00Z"),
     *             @OA\Property(property="date_fin_blocage", type="string", format="date-time", example="2023-12-31T23:59:59Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numero", type="string", example="CPT123456"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="date_debut_blocage", type="string", format="date-time", example="2023-12-01T00:00:00Z"),
     *                 @OA\Property(property="date_fin_blocage", type="string", format="date-time", example="2023-12-31T23:59:59Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé - Admin requis",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seul un administrateur peut bloquer un compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function bloquer(BloquerCompteRequest $request, $compteId)
    {
        try {
            $user = $request->user();
            $adminId = $request->query('admin_id');

            // Allow access if authenticated as admin or admin_id provided
            if (!$user && !$adminId) {
                return $this->errorResponse("ID administrateur requis", 401);
            }

            // Determine admin user
            $admin = $user;
            if (!$admin && $adminId) {
                $admin = User::find($adminId);
            }

            if (!$admin || !in_array(strtolower($admin->role), ['admin', 'administrateur', 'Admin'])) {
                return $this->errorResponse("Seul un administrateur peut bloquer un compte", 403);
            }

            $compte = Compte::find($compteId);
            if (!$compte) {
                return $this->errorResponse("Compte non trouvé", 404);
            }

            // Règle métier : n'autoriser le blocage QUE pour les comptes de type 'epargne'
            // Si le compte n'est pas de type 'epargne' (par ex. 'cheque'), refuser l'opération
            if (!isset($compte->type) || strtolower($compte->type) !== 'epargne') {
                return $this->errorResponse("Seul un compte d'épargne peut être bloqué", 422);
            }

            // Update compte with blocking dates and status
            $updateData = ['statut' => 'bloque'];

            if (Schema::hasColumn('comptes', 'date_debut_blocage')) {
                $updateData['date_debut_blocage'] = $request->date_debut_blocage;
            }

            if (Schema::hasColumn('comptes', 'date_fin_blocage')) {
                $updateData['date_fin_blocage'] = $request->date_fin_blocage;
            }

            $compte->update($updateData);

            return $this->successResponse(
                new CompteResource($compte),
                'Compte bloqué avec succès'
            );

        } catch (\Exception $e) {
            \Log::error('Erreur lors du blocage du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId,
                'request' => $request->all()
            ]);

            return $this->errorResponse("Erreur lors du blocage du compte", 500);
        }
    }
}
