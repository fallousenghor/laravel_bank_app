<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log les utilisateurs qui ont atteint le rate limit
        if ($response->getStatusCode() === 429) {
            $user = $request->user();
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $route = $request->route() ? $request->route()->getName() : 'unknown';

            Log::warning('Rate limit atteint', [
                'user_id' => $user ? $user->id : null,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'route' => $route,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        return $response;
    }
}
