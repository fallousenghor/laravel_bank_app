<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use App\Interfaces\CompteRepositoryInterface;
use App\Traits\ApiResponse;

/**
 * @OA\Info(
 *     title="API de Gestion des Comptes Bancaires",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 * @OA\Server(
 *     url="http://api.banque.example.com",
 *     description="Serveur de production"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur de développement"
 * )
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
    public function index(Request $request)
    {
        try {
            // Authentication is temporarily disabled for testing.
            // If a user is authenticated and has role 'admin' we could apply admin-only
            // restrictions here; for now allow access to list comptes to simplify testing.
            $user = $request->user();

            $query = \App\Models\Compte::with('utilisateur');

            // Filtres
            if ($request->has('type') && in_array($request->type, ['epargne', 'cheque'])) {
                $type = $request->type === 'epargne' ? 'Épargne' : 'Chèque';
                $query->where('type', $type);
            }

            if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque', 'ferme'])) {
                $statut = ucfirst($request->statut);
                $query->where('statut', $statut);
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
            $sortField = $request->get('sort', 'date_creation');
            $sortOrder = $request->get('order', 'desc');

            $allowedSortFields = ['date_creation', 'solde', 'numero'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'date_creation';
            }

            if ($sortField === 'date_creation') {
                $query->orderBy('date_creation', $sortOrder);
            } elseif ($sortField === 'solde') {
                $query->orderBy('solde', $sortOrder);
            } elseif ($sortField === 'numero') {
                $query->orderBy('numero', $sortOrder);
            }

            // Pagination
            $limit = min($request->get('limit', 10), 100);
            $comptes = $query->paginate($limit);

            return $this->paginatedResponse($comptes, 'Comptes récupérés');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse("Erreur interne du serveur", 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/fallou/v1/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Obtenir les détails d'un compte spécifique",
     *     description="Retourne les détails d'un compte bancaire spécifique avec son utilisateur",
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
     *             @OA\Property(property="id", type="string", format="uuid", example="a02eab71-0ae7-48cf-bc37-92d0493737e1"),
     *             @OA\Property(property="numero", type="string", example="CPT123456"),
     *             @OA\Property(property="type", type="string", enum={"Épargne", "Chèque"}, example="Épargne"),
     *             @OA\Property(property="solde", type="number", format="float", example=1000.50),
     *             @OA\Property(property="statut", type="string", enum={"Actif", "Bloqué"}, example="Actif"),
     *             @OA\Property(property="date_creation", type="string", format="date", example="2023-10-23"),
     *             @OA\Property(property="utilisateur_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     )
     * )
     */
    public function show(Request $request, $id = null)
    {
        $id = $id ?? $request->route('id') ?? $request->query('id');
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
    public function mine(Request $request)
    {
        // When unauthenticated, allow passing `user_id` as a query parameter for testing.
        $user = $request->user();
        $userId = $user?->id ?? $request->query('user_id');

        if (!$userId) {
            return $this->errorResponse("Paramètre 'user_id' requis lorsque non authentifié", 400);
        }

        $comptes = $this->compteRepository->getActiveComptesByUserId($userId);
        return $this->successResponse(CompteResource::collection($comptes), 'Comptes de l\'utilisateur');
    }
}
