<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use App\Http\Resources\CompteResource;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API de Gestion des Comptes Bancaires",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/comptes",
     *     tags={"Comptes"},
     *     summary="Liste tous les comptes bancaires",
     *     description="Retourne la liste de tous les comptes bancaires avec leurs utilisateurs",
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
     *     )
     * )
     */
    public function index()
    {
        $comptes = Compte::with('utilisateur')->get();
        return CompteResource::collection($comptes);
    }

    /**
     * @OA\Get(
     *     path="/api/comptes/{id}",
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
    public function show(\App\Http\Requests\ShowCompteRequest $request)
    {
        $id = $request->validated()['id'];
        $compte = Compte::with('utilisateur')->findOrFail($id);
        return new CompteResource($compte);
    }
}
