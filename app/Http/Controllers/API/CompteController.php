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
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="API de Gestion des Comptes Bancaires",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
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
     * Supprimer un compte (non implémenté)
     */
    public function destroy($id)
    {
        $user = request()->user();
        if (!$user || $user->role !== 'admin') {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        return $this->errorResponse('Opération non implémentée', 501);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Récupérer la liste des comptes",
     *     description="Permet à un administrateur de voir tous les comptes, et à un utilisateur de voir uniquement les siens.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Liste des comptes récupérée avec succès"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(ListComptesRequest $request)
    {
        try {
            $validated = $request->validated();
            $filters = [
                'type' => $validated['type'] ?? null,
                'statut' => $validated['statut'] ?? null,
                'search' => $validated['search'] ?? null,
                'sort' => $validated['sort'] ?? 'dateCreation',
                'order' => $validated['order'] ?? 'desc'
            ];

            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Authentification requise', 401);
            }

            if ($user->role !== 'admin') {
                $filters['client_id'] = $user->id;
            }

            $page = $validated['page'] ?? 1;
            $limit = min($validated['limit'] ?? 10, 100);

            $comptes = $this->compteRepository->getAllComptes($filters, $page, $limit);

            $transformed = collect(CompteResource::collection($comptes->getCollection())->resolve());
            $comptes->setCollection($transformed);

            return $this->paginatedResponse($comptes, 'Liste des comptes récupérée avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes: ' . $e->getMessage());
            return $this->errorResponse("Erreur interne du serveur", 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Afficher les détails d'un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Détails du compte"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function show(ShowCompteRequest $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Non authentifié', 401);
        }

        $compte = $this->compteRepository->getCompteById($id);
        if ($user->role !== 'admin' && $compte->client_id !== $user->id) {
            return $this->errorResponse("Accès non autorisé", 403);
        }

        return $this->successResponse(new CompteResource($compte), 'Détails du compte');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/find",
     *     tags={"Comptes"},
     *     summary="Rechercher un compte par numéro",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="numero", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Compte trouvé"),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function findByNumero(Request $request)
    {
        $numero = $request->query('numero');
        if (empty($numero)) {
            return $this->errorResponse('Paramètre numero requis', 422);
        }

        try {
            $compte = $this->compteRepository->getCompteByNumero($numero);
            return $this->successResponse(new CompteResource($compte), 'Détails du compte');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/mine",
     *     tags={"Comptes"},
     *     summary="Afficher les comptes de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Comptes récupérés"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function mine(MineComptesRequest $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Non authentifié', 401);
            }

            $comptes = $this->compteRepository->getActiveComptesByUserId($user->id);
            return $this->successResponse(CompteResource::collection($comptes), 'Comptes de l\'utilisateur');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes utilisateur: ' . $e->getMessage());
            return $this->errorResponse("Erreur interne du serveur", 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Compte créé avec succès")
     * )
     */
    public function store(StoreCompteRequest $request)
    {
        try {
            $validated = $request->validated();
            $clientInput = $validated['client'] ?? [];

            $currentUser = $request->user();
            $client = null;

            if ($currentUser && $currentUser->role !== 'admin') {
                if (!empty($clientInput['id']) && $clientInput['id'] !== $currentUser->id) {
                    return $this->errorResponse('Vous ne pouvez pas créer un compte pour un autre utilisateur', 403);
                }
                $client = $currentUser;
            }

            if (!$client) {
                $client = User::where('email', $clientInput['email'] ?? '')
                    ->orWhere('telephone', $clientInput['telephone'] ?? '')
                    ->first();
            }

            if (!$client) {
                $password = Str::random(8);
                $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $titulaire = $clientInput['titulaire'] ?? '';
                $parts = preg_split('/\s+/', trim($titulaire));
                $prenom = $parts[0] ?? '';
                $nom = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $prenom;

                $client = User::create([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $clientInput['email'] ?? null,
                    'telephone' => $clientInput['telephone'] ?? null,
                    'adresse' => $clientInput['adresse'] ?? null,
                    'nci' => $clientInput['nci'] ?? null,
                    'code' => $code,
                    'password' => Hash::make($password),
                    'role' => 'user',
                ]);

                event(new ClientCreated($client, $password, $code));
            }

            $compteData = [
                'type' => $validated['type'],
                'solde' => $validated['solde'],
                'statut' => 'actif',
                'date_creation' => now(),
                'client_id' => $client->id,
            ];

            if (Schema::hasColumn('comptes', 'devise')) {
                $compteData['devise'] = $validated['devise'] ?? 'FCFA';
            }

            $compte = Compte::create($compteData);
            $compte->load('utilisateur');

            return $this->successResponse(new CompteResource($compte), 'Compte créé avec succès', 201);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du compte: ' . $e->getMessage());
            return $this->errorResponse("Erreur lors de la création du compte", 500);
        }
    }
}
