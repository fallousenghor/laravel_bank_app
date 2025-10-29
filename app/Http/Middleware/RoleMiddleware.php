<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Retrieve permissions/scopes for authenticated user and attach to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // If using Passport, can check token scopes via tokenCan
            $scopes = [];
            try {
                if ($request->user()?->token()) {
                    // token() returns the Token model for Passport
                    $token = $request->user()->token();
                    if ($token && isset($token->scopes) && is_array($token->scopes)) {
                        $scopes = $token->scopes;
                    }
                }
            } catch (\Throwable $e) {
                // ignore - not all token implementations expose scopes the same way
            }

            // Fallback: derive permissions from role attribute on the user
            $role = $user->role ?? 'client';

            $request->attributes->set('user_role', $role);
            $request->attributes->set('user_scopes', $scopes);
        }

        return $next($request);
    }
}
