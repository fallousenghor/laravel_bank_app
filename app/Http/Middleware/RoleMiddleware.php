<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Check if user has the required role
        if ($user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle insuffisant'
            ], 403);
        }

        // Log the access with role information
        \Log::info('Role-based access granted', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'required_role' => $role,
            'route' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
        ]);

        return $next($request);
    }
}
