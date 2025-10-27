<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListComptesRequest;
use App\Http\Requests\ShowCompteRequest;
use App\Http\Requests\MineComptesRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\BloquerCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Models\User;
use App\Events\ClientCreated;
use Illuminate\Http\Request;
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
     * @OA\Get(
     *     path="/senghorfallou/v1/comptes",
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
     *         description="ID de l'admin (pour accès temporaire sans authentification)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
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
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function index(ListComptesRequest $request)
    {
        try {
            $user = $request->user();

            // Allow access without authentication for now, using admin_id parameter if provided
            $adminId = $request->validated()['admin_id'] ?? null;
            if (!$user && !$adminId) {
                return $this->errorResponse("Authentification requise ou paramètre admin_id", 401);
            }

            $query = Compte::with('utilisateur')
                         ->whereIn('type', ['epargne', 'cheque'])
                         ->where('statut', 'actif');

            // For Client, only their own comptes
            if ($user && $user->role !== 'admin') {
                $query->where('utilisateur_id', $user->id);
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
     *     path="/senghorfallou/v1/comptes/{id}",
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
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
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
     *     path="/senghorfallou/v1/comptes/mine",
     *     tags={"Comptes"},
     *     summary="Obtenir les comptes du client connecté",
     *     description="Retourne la liste des comptes actifs du client authentifié ou via user_id en paramètre",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID de l'utilisateur (requis si non authentifié)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
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
                    // DB constraint expects 'Client' (capitalized) as defined in migrations
                    'role' => 'Client',
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
                'utilisateur_id' => $client->id,
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
     * @OA\Post(
     *     path="/senghorfallou/v1/comptes/{compteId}/bloquer",
     *     tags={"Comptes"},
     *     summary="Bloquer un compte bancaire",
     *     description="Bloque un compte bancaire avec des dates de début et fin de blocage",

     *     @OA\Parameter(
     *         name="admin_id",
     *         in="query",
     *         description="ID de l'admin (pour accès temporaire sans authentification)",
     *         required=true,
     *         @OA\Schema(type="integer")
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
     *         description="Accès non autorisé - Admin ou propriétaire requis",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seul un administrateur ou le propriétaire du compte peut bloquer un compte")
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
            // Vérifier si l'ID admin est fourni
            $adminId = $request->query('admin_id');
            if (!$adminId) {
                return $this->errorResponse("ID administrateur requis", 401);
            }

            // Vérifier si l'utilisateur est un admin
            $admin = User::find($adminId);
            if (!$admin || $admin->role !== 'admin') {
                return $this->errorResponse("Seul un administrateur peut bloquer un compte", 403);
            }

            $compte = Compte::find($compteId);
            if (!$compte) {
                return $this->errorResponse("Compte non trouvé", 404);
            }

            // Update compte with blocking dates and status
            $compte->update([
                'statut' => 'bloque',
                'date_debut_blocage' => $request->date_debut_blocage,
                'date_fin_blocage' => $request->date_fin_blocage,
            ]);

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
