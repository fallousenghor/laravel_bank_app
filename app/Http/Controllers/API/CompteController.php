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
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Serveur (défini par L5_SWAGGER_CONST_HOST)"
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
     *     path="/fallou/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Liste tous les comptes bancaires",
     *     description="Retourne la liste de tous les comptes bancaires avec leurs utilisateurs",
     *     @OA\Parameter(
     *         name="as_admin",
     *         in="query",
     *         description="Paramètre pour simuler un accès administrateur (utiliser 1 pour les tests)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={1})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="a02eab71-0ae7-48cf-bc37-92d0493737e1"),
     *                 @OA\Property(property="numero", type="string", example="CPT123456"),
     *                 @OA\Property(property="type", type="string", enum={"Épargne", "Chèque"}, example="Épargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1000.50),
     *                 @OA\Property(property="statut", type="string", enum={"Actif", "Bloqué"}, example="Actif"),
     *                 @OA\Property(property="date_creation", type="string", format="date", example="2023-10-23"),
     *                 @OA\Property(property="utilisateur_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé. Pour les tests, ajoutez ?as_admin=1")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Pour permettre les tests sans authentification, on accepte le paramètre
        // de query `as_admin=1` qui simule un appel par un admin.
        $user = $request->user();
        $isAdmin = ($user && $user->role === 'admin') || $request->query('as_admin') == '1';

        if (!$isAdmin) {
            return $this->errorResponse("Accès non autorisé. Pour les tests, ajoutez ?as_admin=1", 403);
        }

        // Retourne uniquement les comptes actifs de type Épargne ou Chèque
        $comptes = $this->compteRepository->getActiveComptes();
        return $this->successResponse(CompteResource::collection($comptes), 'Comptes récupérés');
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
     *     path="/fallou/v1/comptes/mine",
     *     tags={"Comptes"},
     *     summary="Obtenir les comptes du client connecté",
     *     description="Retourne la liste des comptes actifs du client authentifié",
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID de l'utilisateur pour les tests (remplace l'authentification)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes de l'utilisateur récupérée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="type", type="string", enum={"Épargne", "Chèque"}),
     *                 @OA\Property(property="solde", type="number", format="float"),
     *                 @OA\Property(property="statut", type="string", enum={"Actif", "Bloqué"}),
     *                 @OA\Property(property="date_creation", type="string", format="date"),
     *                 @OA\Property(property="utilisateur_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié. Pour les tests, fournissez ?user_id=<id>")
     *         )
     *     )
     * )
     */
    public function mine(Request $request)
    {
        // Permettre les tests sans authentification en passant `user_id` en query string.
        $user = $request->user();
        $userId = $user?->id ?? $request->query('user_id');

        if (!$userId) {
            return $this->errorResponse("Non authentifié. Pour les tests, fournissez ?user_id=<id>", 401);
        }

        $comptes = $this->compteRepository->getActiveComptesByUserId($userId);
        return $this->successResponse(CompteResource::collection($comptes), 'Comptes de l\'utilisateur');
    }
}
