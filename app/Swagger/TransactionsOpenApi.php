<?php

/**
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *   schema="Transaction",
 *   type="object",
 *   @OA\Property(property="id", type="string", format="uuid"),
 *   @OA\Property(property="montant", type="number", format="float"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="date", type="string", format="date-time"),
 *   @OA\Property(
 *     property="compte",
 *     type="object",
 *     @OA\Property(property="numero", type="string")
 *   )
 * )
 */

// Ce fichier contient uniquement des annotations OpenAPI (swagger-php)
// pour la documentation des endpoints Transactions.

// Ce fichier expose des annotations OpenAPI (swagger-php) au scanner.
// Il est volontairement sans namespace pour garantir que les composants
// sont détectés correctement par swagger-php.

