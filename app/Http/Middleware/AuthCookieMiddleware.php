<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class AuthCookieMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for token in cookie first
        $token = $request->cookie('access_token');

        if ($token) {
            // Set the Authorization header if token is found in cookie
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        // Continue with normal Passport authentication
        return $next($request);
    }
}
