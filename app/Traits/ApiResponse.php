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
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
                'itemsPerPage' => $paginator->perPage(),
                'hasNext' => $paginator->hasMorePages(),
                'hasPrevious' => $paginator->currentPage() > 1,
            ];

            $response = [
                'success' => true,
                'data' => $paginator->items(),
                'pagination' => $pagination,
                'links' => [
                    'self' => $paginator->url($paginator->currentPage()),
                    'next' => $paginator->hasMorePages() ? $paginator->url($paginator->currentPage() + 1) : null,
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                ]
            ];

            if ($message) {
                $response['message'] = $message;
            }

            return response()->json($response, $status);
        }

        return $this->successResponse($paginator, $message, $status);
    }
}
