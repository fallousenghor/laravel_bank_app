<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http as HttpClient;
use OpenApi\Annotations as OA;

/**
 * @OA\Server(
 *     url="/api/v1",
 *     description="Serveur API"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur et retourne un token d'accès et un token de rafraîchissement",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="refresh_token", type="string", example="def50200..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=900),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Connexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Email ou mot de passe incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email ou mot de passe incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }


        // Use password grant to obtain access + refresh tokens via internal /oauth/token
    // Read password client credentials from config first (works when config is cached)
    $passwordClientId = config('services.passport.password_client_id', env('PASSPORT_PASSWORD_CLIENT_ID'));
    $passwordClientSecret = config('services.passport.password_client_secret', env('PASSPORT_PASSWORD_CLIENT_SECRET'));

        if (! $passwordClientId || ! $passwordClientSecret) {
            logger()->error('Password client credentials missing in env');
            return response()->json([
                'success' => false,
                'message' => 'Authentication server not configured (password client missing).'
            ], 500);
        }

        // compute scopes based on user permissions/role
        $scopes = method_exists($user, 'getScopes') ? $user->getScopes() : [];
        // Filter scopes against Passport-declared scopes to avoid invalid_scope errors
        $availableScopes = \Laravel\Passport\Passport::scopeIds();
        if (is_array($scopes) && count($availableScopes) > 0) {
            $scopes = array_values(array_filter($scopes, fn($s) => in_array($s, $availableScopes)));
        } else {
            // if no available scopes declared, clear custom scopes to avoid rejection
            $scopes = [];
        }

        // Dispatch internally to /oauth/token to avoid external HTTP calls (prevents curl timeouts)
        $params = [
            'grant_type' => 'password',
            'client_id' => $passwordClientId,
            'client_secret' => $passwordClientSecret,
            'username' => $request->email,
            'password' => $request->password,
            'scope' => implode(' ', $scopes),
        ];

        // Build a proper application/x-www-form-urlencoded request body so Passport recognizes grant_type
        // Create request and explicitly populate the request bag so Passport sees grant_type
        $tokenRequest = \Illuminate\Http\Request::create(
            '/oauth/token',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']
        );
        // ensure parameters are available in the request bag
        $tokenRequest->request->add($params);
        $tokenResponse = app()->handle($tokenRequest);

        $status = $tokenResponse->getStatusCode();
        $payload = json_decode($tokenResponse->getContent(), true) ?: [];

        if ($status >= 400) {
            logger()->error('Failed to obtain oauth token (internal dispatch)', ['status' => $status, 'body' => $payload]);
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed (oauth server).'
            ], $status ?: 500);
        }

        // Set cookies: access token (short), refresh token (longer)
        $accessTtlMinutes = intval(config('session.lifetime') ?? 15);
        $refreshTtlDays = 14; // keep refresh token cookie for 14 days

        $accessCookie = cookie('access_token', $payload['access_token'], $accessTtlMinutes, '/', null, false, true, false, 'Lax');
        $refreshCookie = cookie('refresh_token', $payload['refresh_token'] ?? '', $refreshTtlDays * 24 * 60, '/', null, false, true, false, 'Lax');

        // Build response data: include tokens only (and role as separate claim field)
        $responseData = [
            'access_token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'] ?? null,
            'token_type' => $payload['token_type'] ?? 'Bearer',
            'expires_in' => $payload['expires_in'] ?? 900,
        ];

        // Optionally include user's role in response (not inside JWT claims yet)
        $responseMeta = [
            'role' => $user->role ?? null,
            'scopes' => $scopes,
        ];

        return response()->json([
            'success' => true,
            'data' => $responseData,
            'meta' => $responseMeta,
            'message' => 'Connexion réussie'
        ])->cookie($accessCookie)->cookie($refreshCookie);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"Authentification"},
     *     summary="Rafraîchir le token d'accès",
     *     description="Utilise un token de rafraîchissement pour obtenir un nouveau token d'accès",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="def50200...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token renouvelé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="refresh_token", type="string", example="def50200..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=900)
     *             ),
     *             @OA\Property(property="message", type="string", example="Token renouvelé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token de rafraîchissement invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token de rafraîchissement invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }


        // Use refresh token grant via /oauth/token
        $refreshToken = $request->input('refresh_token') ?? $request->cookie('refresh_token');

        if (! $refreshToken) {
            return response()->json([
                'success' => false,
                'message' => 'refresh_token is required'
            ], 422);
        }

        $passwordClientId = env('PASSPORT_PASSWORD_CLIENT_ID');
        $passwordClientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET');

        $refreshParams = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $passwordClientId,
            'client_secret' => $passwordClientSecret,
            'scope' => '',
        ];

        $refreshRequest = \Illuminate\Http\Request::create(
            '/oauth/token',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']
        );
        $refreshRequest->request->add($refreshParams);
        $refreshResponse = app()->handle($refreshRequest);

        $status = $refreshResponse->getStatusCode();
        $payload = json_decode($refreshResponse->getContent(), true) ?: [];

        if ($status >= 400) {
            logger()->error('Failed to refresh oauth token (internal dispatch)', ['status' => $status, 'body' => $payload]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to refresh token.'
            ], $status ?: 500);
        }

        $accessTtlMinutes = intval(config('session.lifetime') ?? 15);
        $refreshTtlDays = 14;

        $accessCookie = cookie('access_token', $payload['access_token'], $accessTtlMinutes, '/', null, false, true, false, 'Lax');
        $refreshCookie = cookie('refresh_token', $payload['refresh_token'] ?? '', $refreshTtlDays * 24 * 60, '/', null, false, true, false, 'Lax');

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $payload['access_token'],
                'refresh_token' => $payload['refresh_token'] ?? null,
                'token_type' => $payload['token_type'] ?? 'Bearer',
                'expires_in' => $payload['expires_in'] ?? 900,
            ],
            'message' => 'Token renouvelé avec succès'
        ])->cookie($accessCookie)->cookie($refreshCookie);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentification"},
     *     summary="Déconnexion utilisateur",
     *     description="Révoque le token d'accès actuel de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Revoke current access token
        $token = $request->user()->token();
        if ($token) {
            $token->revoke();

            // Also revoke related refresh tokens
            \DB::table('oauth_refresh_tokens')->where('access_token_id', $token->id)->update(['revoked' => true]);
        }

        // Clear the cookies
        $accessCookie = cookie('access_token', '', -1);
        $refreshCookie = cookie('refresh_token', '', -1);

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ])->cookie($accessCookie)->cookie($refreshCookie);
    }
}
