<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait ApiResponse
 *
 * Fournit des helpers réutilisables pour formater les réponses JSON de l'API.
 */
trait ApiResponse
{
    /**
     * Réponse JSON de succès standardisée.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message ?? 'Request successful',
            'data' => $data,
        ];

        return response()->json($payload, $status);
    }

    /**
     * Réponse JSON d'erreur standardisée.
     *
     * @param string|null $message
     * @param int $status
     * @param mixed|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(?string $message = null, int $status = 400, $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message ?? 'An error occurred',
        ];

        if (!is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Réponse JSON paginée standardisée pour les paginators Laravel.
     *
     * Si un paginator `LengthAwarePaginator` est passé, la réponse contiendra
     * les métadonnées de pagination et la clé `data` pour les éléments.
     * Sinon, renvoie simplement successResponse($paginator).
     *
     * @param mixed $paginator
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function paginatedResponse($paginator, ?string $message = null, int $status = 200): JsonResponse
    {
        if ($paginator instanceof LengthAwarePaginator) {
            $pagination = [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'data' => $paginator->items(),
            ];

            return $this->successResponse($pagination, $message, $status);
        }

        return $this->successResponse($paginator, $message, $status);
    }
}
