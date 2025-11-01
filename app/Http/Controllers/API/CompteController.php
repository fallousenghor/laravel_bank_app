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
use App\Http\Requests\SearchClientRequest;
use App\Http\Resources\UserResource;
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
 *     description="API pour la gestion des comptes bancaires avec autorisations basées sur les rôles"
 * )
 * (Servers are generated from configuration (APP_URL / SWAGGER_BASE_URL) so they are set per-environment.)
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token JWT requis. Les administrateurs ont tous les droits, les clients ne peuvent voir/modifier que leurs propres comptes."
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
     *     path="/senghorfallou/v1/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Supprimer un compte",
     *     description="Effectue une suppression douce (soft delete) d'un compte. Les clients ne peuvent supprimer que leurs propres comptes.",
     *     security={{"bearerAuth":{}}},
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

        // Authorization: only authenticated users may perform this action.
        // Policies ensure admin or owner access.
        $user = request()->user();
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $this->authorize('delete', $compte);

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
     *     path="/senghorfallou/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes",
     *     description="Retourne la liste paginée des comptes bancaires. Les administrateurs voient tous les comptes, les clients ne voient que leurs propres comptes.",
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
     *                 @OA\Property(property="self", type="string", example="/senghorfallou/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/senghorfallou/v1/comptes?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/senghorfallou/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/senghorfallou/v1/comptes?page=3&limit=10")
     *             )
     *         )
     *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non authentifié",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Authentification requise")
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
    *             @OA\Property(property="message", type="string", example="Paramètres invalides."),
    *             @OA\Property(
    *                 property="errors",
    *                 type="object"
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
            // Require authenticated user — endpoint no longer supports admin_id fallback
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Authentification requise', 401);
            }

            // Get paginated results with filters
            $comptes = $this->compteRepository->getAllComptes(
                $filters,
                $validated['page'] ?? 1,
                $validated['limit'] ?? 10
            );

            // If authenticated user is not an admin, filter results to their accounts only
            if ($user->role !== 'admin') {
                $filters['client_id'] = $user->id;
            }

            $comptes = $this->compteRepository->getAllComptes(
                $filters,
                $validated['page'] ?? 1,
                $validated['limit'] ?? 10
            );

            $query = Compte::with('utilisateur');

            // Construction sécurisée de la requête avec des paramètres liés
            $query->where(function($q) {
                $q->where('type', '=', 'epargne')
                  ->orWhere('type', '=', 'cheque');
            })
            ->where('statut', '=', 'actif');

            // For non-admin users, only their own comptes
            if ($user->role !== 'admin') {
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
                    'user' => $user ? ['id' => $user->id, 'role' => $user->role] : 'non authentifié'
                ]);

                return $this->errorResponse("Erreur interne du serveur - " . $e->getMessage(), 500);
            }
    }

    /**
     * @OA\Get(
     *     path="/senghorfallou/v1/comptes/{numero}",
     *     tags={"Comptes"},
     *     summary="Obtenir les détails d'un compte spécifique",
     *     description="Retourne les détails d'un compte bancaire spécifique. Les administrateurs peuvent voir tous les comptes, les clients ne peuvent voir que leurs propres comptes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte (ex: CPT123456)",
     *         required=true,
     *         @OA\Schema(type="string")
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
    public function show(ShowCompteRequest $request, $numero)
    {
        // The route param `numero` represents the account number (numero column).

        // Find by account number.
        $compte = Compte::where('numero', $numero)->first();

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Authorization: authenticated user only (policies check admin/owner)
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $this->authorize('view', $compte);

        return $this->successResponse(new CompteResource($compte), 'Détails du compte');
    }

    /**
     * Return the active accounts of the authenticated user.
     *
     * NOTE: This endpoint is intentionally not documented in the public Swagger/OpenAPI
     * documentation. Keep the implementation but avoid OpenAPI annotations here so that
     * l5-swagger will no longer include it in the generated docs.
     */
    public function mine(MineComptesRequest $request)
    {
        try {
            // Require authenticated user — identify via token
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse("Authentification requise", 401);
            }

            $comptes = $this->compteRepository->getActiveComptesByUserId($user->id);
            return $this->successResponse(CompteResource::collection($comptes), 'Comptes de l\'utilisateur');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes utilisateur: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->errorResponse("Erreur interne du serveur", 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/senghorfallou/v1/clients/search",
     *     tags={"Clients"},
     *     summary="Rechercher un client par téléphone ou NCI",
     *     description="Permet à un administrateur de rechercher un client en fournissant le champ `telephone` ou `nci` (au moins un).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="query",
     *         description="Numéro de téléphone du client (format local, ex: 771234567)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="nci",
     *         in="query",
     *         description="Numéro NCI du client",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="prenom", type="string", example="Amadou"),
     *                 @OA\Property(property="nom", type="string", example="Diallo"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="comptes", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numero", type="string")
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Client trouvé")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Authentification requise"),
     *     @OA\Response(response=403, description="Accès non autorisé - admin requis"),
     *     @OA\Response(response=404, description="Client non trouvé")
     * )
     */
    public function searchClient(SearchClientRequest $request)
    {
        try {
            $validated = $request->validated();

            $telephone = $validated['telephone'] ?? null;
            $nci = $validated['nci'] ?? null;

            $userQuery = User::query();

            $userQuery->where(function ($q) use ($telephone, $nci) {
                if ($telephone) {
                    $q->where('telephone', $telephone);
                }

                if ($nci) {
                    // use orWhere only inside the group so that multiple params are ORed together
                    if ($telephone) {
                        $q->orWhere('nci', $nci);
                    } else {
                        $q->where('nci', $nci);
                    }
                }
            });

            $client = $userQuery->with('comptes')->first();

            if (!$client) {
                return $this->errorResponse('Client non trouvé', 404);
            }

            return $this->successResponse(new UserResource($client), 'Client trouvé');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche du client: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/senghorfallou/v1/comptes",
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

            // Require authenticated user — token is the canonical identity
            $authUser = $request->user();
            if (!$authUser) {
                return $this->errorResponse('Authentification requise', 401);
            }

            // Only admins are allowed to create new comptes. Admins may specify a client payload
            // to create an account for another user.
            $client = null;
            $clientInput = $validated['client'] ?? [];

            if ($authUser->role !== 'admin') {
                return $this->errorResponse('Seul un administrateur peut créer un compte', 403);
            } else {
                // Admin flow: try to find or create the client from payload
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
     *     path="/senghorfallou/v1/comptes/{compteId}",
     *     tags={"Comptes"},
     *     summary="Mettre à jour les informations du client",
     *     description="Modifie les informations du client associé à un compte bancaire. Les clients ne peuvent modifier que leurs propres informations.",
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

            // Get the associated user
            $user = $compte->utilisateur;
            if (!$user) {
                return $this->errorResponse("Utilisateur associé non trouvé", 404);
            }

            // Authorization: authenticated user only. Policies enforce admin or owner.
            $authUser = $request->user();
            if (!$authUser) {
                return $this->errorResponse('Authentification requise', 401);
            }

            $this->authorize('update', $compte);

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
     *     path="/senghorfallou/v1/comptes/{compteId}/bloquer",
     *     tags={"Comptes"},
     *     summary="Bloquer un compte bancaire",
     *     description="Bloque un compte bancaire avec des dates de début et fin de blocage. Réservé aux administrateurs.",
     *     security={{"bearerAuth":{}}},
    *
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
            // Require authenticated admin
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse("Authentification requise", 401);
            }

            if ($user->role !== 'admin') {
                return $this->errorResponse("Seul un administrateur peut bloquer un compte", 403);
            }

            $compte = Compte::find($compteId);
            if (!$compte) {
                return $this->errorResponse("Compte non trouvé", 404);
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
