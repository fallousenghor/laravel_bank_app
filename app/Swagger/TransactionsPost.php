<?php

namespace App\Swagger;

/**
 * @OA\Post(
 *     path="/senghorfallou/v1/transactions",
 *     tags={"Transactions"},
 *     summary="Créer une transaction (depot, retrait, virement)",
 *     description="Crée une transaction de type 'depot', 'retrait' ou 'virement'. Pour un 'virement', fournissez 'source_numero' et 'destination_numero'. Clients: opérations sur leurs propres comptes; admins: tous les comptes.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"type","montant"},
 *             @OA\Property(property="type", type="string", example="virement", description="depot|retrait|virement"),
 *             @OA\Property(property="numero_compte", type="string", example="CPT123456"),
 *             @OA\Property(property="source_numero", type="string", example="CPT123456"),
 *             @OA\Property(property="destination_numero", type="string", example="CPT654321"),
 *             @OA\Property(property="montant", type="number", format="float", example=150.00),
 *             @OA\Property(property="libelle", type="string", example="Paiement facture"),
 *             example={"type":"virement","source_numero":"CPT123456","destination_numero":"CPT654321","montant":150.00,"libelle":"Transfert"}
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Transaction créée",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Virement effectué avec succès"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="debit", type="object"),
 *                 @OA\Property(property="credit", type="object")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=403, description="Accès refusé - compte non autorisé"),
 *     @OA\Response(response=422, description="Erreur de validation / solde insuffisant"),
 *     @OA\Response(response=500, description="Erreur interne du serveur")
 * )
 */
class TransactionsPost
{
    // This class exists solely for Swagger/OpenAPI annotation scanning.
}
