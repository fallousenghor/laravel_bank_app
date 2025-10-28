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
use App\Http\Resources\API\SuccessResource;
use App\Http\Resources\API\ErrorResource;
use App\Http\Resources\API\PaginatedResource;
use App\Messages\fr\ErrorMessages;
use App\Messages\fr\SuccessMessages;
use App\Models\Compte;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use OpenApi\Annotations as OA;
use App\Interfaces\CompteRepositoryInterface;
use App\Services\CompteService;
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
    private $compteService;

    public function __construct(CompteRepositoryInterface $compteRepository, CompteService $compteService)
    {
        $this->compteRepository = $compteRepository;
        $this->compteService = $compteService;
    }

    /**
     * @OA\Delete(
     *     path="/senghorfallou/v1/comptes/{id}",
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
        try {
            $compte = $this->compteService->fermerCompte($id);

            return new SuccessResource([
                'id' => $compte->id,
                'numeroCompte' => $compte->numero,
                'statut' => $compte->statut,
                'dateFermeture' => now()
            ], SuccessMessages::COMPTE_FERME->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la fermeture du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $id
            ]);

            return new ErrorResource(ErrorMessages::ERREUR_FERMETURE_COMPTE->value, [], 500);
        }
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
     *                 @OA\Property(property="self", type="string", example="/senghorfallou/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/senghorfallou/v1/comptes?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/senghorfallou/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/senghorfallou/v1/comptes?page=3&limit=10")
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
            $validated = $request->validated();
            $user = $request->user();
            $adminId = $validated['admin_id'] ?? null;

            // Validate authentication or admin access
            if (!$user && !$adminId) {
                return new ErrorResource(ErrorMessages::AUTHENTIFICATION_REQUISE->value, [], 401);
            }

            // If admin_id is provided, verify the user is an admin
            if ($adminId) {
                $admin = \App\Models\User::find($adminId);
                if (!$admin || $admin->role !== 'admin') {
                    return new ErrorResource(ErrorMessages::ACCES_NON_AUTORISE->value, [], 403);
                }
            }

            // Setup filters
            $filters = [
                'type' => $validated['type'] ?? null,
                'statut' => $validated['statut'] ?? null,
                'search' => $validated['search'] ?? null,
                'sort' => $validated['sort'] ?? 'dateCreation',
                'order' => $validated['order'] ?? 'desc'
            ];

            $comptes = $this->compteService->getFilteredComptesPaginated(
                $filters,
                $validated['page'] ?? 1,
                $validated['limit'] ?? 10,
                $user
            );

            return new PaginatedResource($comptes, SuccessMessages::COMPTES_RECUPERES->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'admin_id' => $adminId ?? 'non défini',
                'user' => $user ? ['id' => $user->id, 'role' => $user->role] : 'non authentifié'
            ]);

            return new ErrorResource(ErrorMessages::ERREUR_RECUPERATION_COMPTES->value, [], 500);
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
            return new SuccessResource(new CompteResource($compte), SuccessMessages::COMPTE_RECUPERE->value);
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
                return new ErrorResource(ErrorMessages::PARAMETRE_ADMIN_ID_REQUIS->value, [], 400);
            }

            // Verify if user exists
            $userExists = \App\Models\User::where('id', $userId)->exists();
            if (!$userExists) {
                return new ErrorResource(ErrorMessages::COMPTE_NON_TROUVE->value, [], 404);
            }

            $comptes = $this->compteRepository->getActiveComptesByUserId($userId);
            return new SuccessResource(CompteResource::collection($comptes), SuccessMessages::COMPTES_RECUPERES->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes utilisateur: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? 'non défini',
                'request' => $request->all()
            ]);
            return new ErrorResource(ErrorMessages::ERREUR_INTERNE->value, [], 500);
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

            $compteData = [
                'type' => $validated['type'],
                'solde' => $validated['solde'],
            ];

            if (Schema::hasColumn('comptes', 'devise')) {
                $compteData['devise'] = $validated['devise'] ?? 'FCFA';
            }

            $compte = $this->compteService->createCompteWithClient($compteData, $validated['client'] ?? []);

            return new SuccessResource(new CompteResource($compte), SuccessMessages::COMPTE_CREE->value, 201);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return new ErrorResource(ErrorMessages::ERREUR_CREATION_COMPTE->value, [], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/senghorfallou/v1/comptes/{compteId}",
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

            $compte = $this->compteService->updateCompteClientInfo($compteId, $validated);

            return new SuccessResource(new CompteResource($compte), SuccessMessages::COMPTE_MIS_A_JOUR->value);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId,
                'request' => $request->all()
            ]);

            return new ErrorResource(ErrorMessages::ERREUR_MISE_A_JOUR_COMPTE->value, [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/senghorfallou/v1/comptes/{compteId}/bloquer",
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
            return new ErrorResource(ErrorMessages::ID_ADMINISTRATEUR_REQUIS->value, [], 401);
            }

            // Determine admin user
            $admin = $user;
            if (!$admin && $adminId) {
                $admin = User::find($adminId);
            }

            if (!$admin || !in_array(strtolower($admin->role), ['admin', 'administrateur', 'Admin'])) {
                return new ErrorResource(ErrorMessages::ADMIN_REQUIS_BLOQUER_COMPTE->value, [], 403);
            }

            $compte = $this->compteService->bloquerCompte($compteId, [
                'date_debut_blocage' => $request->date_debut_blocage,
                'date_fin_blocage' => $request->date_fin_blocage,
            ]);

            return new SuccessResource(new CompteResource($compte), SuccessMessages::COMPTE_BLOQUE->value);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du blocage du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $compteId,
                'request' => $request->all()
            ]);

            return new ErrorResource(ErrorMessages::ERREUR_BLOCAGE_COMPTE->value, [], 500);
        }
    }
}
