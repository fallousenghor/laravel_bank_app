<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="TransactionItem",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="montant", type="number", format="float", example=150.00),
 *     @OA\Property(property="type", type="string", example="retrait"),
 *     @OA\Property(property="date", type="string", format="date-time", example="2025-11-02T20:45:00Z"),
 *     @OA\Property(
 *         property="compte",
 *         type="object",
 *         @OA\Property(property="numero", type="string", example="CPT123456")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="TransactionsListResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Transactions récupérées"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TransactionItem")
 *     ),
 *     @OA\Property(property="pagination", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="TransactionCreateRequest",
 *     type="object",
 *     @OA\Property(property="type", type="string", example="virement", description="depot|retrait|virement"),
 *     @OA\Property(property="numero_compte", type="string", example="CPT123456"),
 *     @OA\Property(property="source_numero", type="string", example="CPT123456"),
 *     @OA\Property(property="destination_numero", type="string", example="CPT654321"),
 *     @OA\Property(property="montant", type="number", format="float", example=150.00),
 *     @OA\Property(property="libelle", type="string", example="Paiement facture")
 * )
 *
 * @OA\Schema(
 *     schema="TransactionCreateResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Virement effectué avec succès"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="debit", ref="#/components/schemas/TransactionItem"),
 *         @OA\Property(property="credit", ref="#/components/schemas/TransactionItem")
 *     )
 * )
 */
class TransactionsSchemas
{
    // This file only contains OpenAPI schema annotations for l5-swagger scanning.
}
