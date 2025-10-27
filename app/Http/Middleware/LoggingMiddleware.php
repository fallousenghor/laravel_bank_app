<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log incoming request
        \Log::info('Request started', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'host' => $request->getHost(),
            'operation' => $this->getOperationName($request),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // in milliseconds

        // Log response with operation details
        \Log::info('Request completed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
            'host' => $request->getHost(),
            'operation' => $this->getOperationName($request),
            'timestamp' => now()->toISOString(),
            'resource' => $this->getResourceName($request),
        ]);

        return $response;
    }

    /**
     * Get operation name based on route
     */
    private function getOperationName(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        if (str_contains($path, 'comptes')) {
            if ($method === 'POST' && str_contains($path, 'bloquer')) {
                return 'Bloquer Compte';
            } elseif ($method === 'PATCH') {
                return 'Mettre à jour Compte';
            } elseif ($method === 'POST') {
                return 'Créer Compte';
            } elseif ($method === 'GET') {
                return 'Consulter Compte';
            }
        }

        return $method . ' ' . $path;
    }

    /**
     * Get resource name for logging
     */
    private function getResourceName(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, 'comptes')) {
            return 'Compte';
        }

        return 'Unknown';
    }
}
