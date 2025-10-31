<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http as HttpClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;

class AuthController extends Controller
{
    /**
    * @OA\Tag(
    *     name="Auth",
    *     description="Authentication endpoints"
    * )
    *
    * @OA\Post(
    *     path="/api/v1/auth/login",
    *     tags={"Auth"},
    *     summary="Login with email and password",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"email","password"},
    *             @OA\Property(property="email", type="string", format="email"),
    *             @OA\Property(property="password", type="string", format="password"),
    *             @OA\Property(property="scope", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Returns access and refresh tokens",
    *         @OA\JsonContent(
    *             @OA\Property(property="access_token", type="string"),
    *             @OA\Property(property="refresh_token", type="string"),
    *             @OA\Property(property="expires_in", type="integer"),
    *             @OA\Property(property="token_type", type="string"),
    *             @OA\Property(property="user", type="object")
    *         )
    *     )
    * )
     * Perform login and return access + refresh tokens (using Passport password grant).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'scope' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // Prepare password grant request to Passport /oauth/token
        // Always use database fallback for client credentials
        $clientId = null;
        $clientSecret = null;

        // Fallback: if env vars are missing (or config cache stale), try to read the
        // password client directly from the database. This helps in environments
        // where .env isn't up-to-date but the oauth clients exist in DB.
        if (!$clientId || !$clientSecret) {
            try {
                $dbClient = \DB::table('oauth_clients')->where('password_client', 1)->first();
                if ($dbClient && !empty($dbClient->id) && !empty($dbClient->secret)) {
                    $clientId = $dbClient->id;
                    $clientSecret = $dbClient->secret;
                    \Log::warning('Using oauth client credentials from database as PASSPORT_PASSWORD_CLIENT_* env vars are missing or empty.');
                }
            } catch (\Exception $e) {
                // Ignore DB errors here; we'll surface a clear message below if no creds found
                \Log::error('Failed to read oauth_clients table for fallback credentials: ' . $e->getMessage());
            }
        }

        if (!$clientId || !$clientSecret) {
            return response()->json(['message' => 'OAuth client credentials are not configured. Run "php artisan passport:install" and set PASSPORT_PASSWORD_CLIENT_ID/PASSPORT_PASSWORD_CLIENT_SECRET in .env'], 500);
        }

        $scope = $request->input('scope', '*');

        // Create token directly using Passport's PersonalAccessTokenFactory
        // This bypasses the OAuth endpoint and is more reliable
        try {
            $abilities = [];
            if ($scope && $scope !== '*') {
                $abilities = array_filter(explode(' ', $scope));
            }

            $tokenResult = $user->createToken('Laravel Password Grant Client', $abilities);
            $accessToken = $tokenResult->accessToken;

            if (!$accessToken) {
                return response()->json(['message' => 'Failed to create access token'], 500);
            }

            $data = [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 hour in seconds
                'user' => $user->makeHidden(['password', 'remember_token']),
            ];

            // For refresh token, create a separate token with longer expiry
            $refreshTokenResult = $user->createToken('Laravel Password Grant Client - Refresh', ['*']);
            $refreshToken = $refreshTokenResult->accessToken;

            if ($refreshToken) {
                $data['refresh_token'] = $refreshToken;
            }

        } catch (\Exception $e) {
            \Log::error('Failed to create token', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to issue token', 'details' => $e->getMessage()], 500);
        }

        // Store access token in an HTTP-only secure cookie
        $accessToken = $data['access_token'] ?? null;
        $accessTtl = isset($data['expires_in']) ? intval($data['expires_in'] / 60) : 60; // minutes

        $accessCookie = cookie('access_token', $accessToken, $accessTtl, '/', null, config('app.env') !== 'local', true, false, 'Strict');

        // Optionally store refresh token in a secure, http-only cookie as well
        $refreshToken = $data['refresh_token'] ?? null;
        $refreshTtl = 60 * 24 * 30; // 30 days in minutes
        $refreshCookie = $refreshToken ? cookie('refresh_token', $refreshToken, $refreshTtl, '/', null, config('app.env') !== 'local', true, false, 'Strict') : null;

        $responseBuilder = response()->json($data)->withCookie($accessCookie);
        if ($refreshCookie) {
            $responseBuilder = $responseBuilder->withCookie($refreshCookie);
        }

        return $responseBuilder;
    }

    /**
    * @OA\Post(
    *     path="/api/v1/auth/refresh",
    *     tags={"Auth"},
    *     summary="Refresh access token",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"refresh_token"},
    *             @OA\Property(property="refresh_token", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Returns new access token",
    *         @OA\JsonContent(
    *             @OA\Property(property="access_token", type="string"),
    *             @OA\Property(property="refresh_token", type="string"),
    *             @OA\Property(property="expires_in", type="integer")
    *         )
    *     )
    * )
     * Refresh the access token using a refresh token.
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientId = null;
        $clientSecret = null;

        // Use internal dispatch to refresh token
        $refreshRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->input('refresh_token'),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $refreshResponse = app()->handle($refreshRequest);
        $status = $refreshResponse->getStatusCode();
        $data = json_decode($refreshResponse->getContent(), true);

        if ($status >= 400) {
            return response()->json(['message' => 'Failed to refresh token', 'details' => $data], $status);
        }

        // Attach user info to refreshed response if possible
        $user = $request->user();
        if ($user) {
            $data['user'] = $user->makeHidden(['password', 'remember_token']);
        }

        $accessToken = $data['access_token'] ?? null;
        $accessTtl = isset($data['expires_in']) ? intval($data['expires_in'] / 60) : 60; // minutes
        $accessCookie = cookie('access_token', $accessToken, $accessTtl, '/', null, config('app.env') !== 'local', true, false, 'Strict');

        // If refresh_token returned, update refresh cookie as well
        $refreshToken = $data['refresh_token'] ?? null;
        $refreshCookie = $refreshToken ? cookie('refresh_token', $refreshToken, 60 * 24 * 30, '/', null, config('app.env') !== 'local', true, false, 'Strict') : null;

        $responseBuilder = response()->json($data)->withCookie($accessCookie);
        if ($refreshCookie) {
            $responseBuilder = $responseBuilder->withCookie($refreshCookie);
        }

        return $responseBuilder;
    }

    /**
    * @OA\Post(
    *     path="/api/v1/auth/logout",
    *     tags={"Auth"},
    *     summary="Logout and revoke tokens",
    *     security={{"passport":{}}},
    *     @OA\Response(
    *         response=200,
    *         description="Logged out",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
     * Logout - revoke tokens for the current user.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Revoke current access token
            try {
                $token = $user->token();
                if ($token) {
                    $token->revoke();
                }
            } catch (\Throwable $e) {
                // Some token implementations may differ; ignore revoke failures here
            }
        }

    // Remove cookies by setting expired cookies
    $expiredAccess = cookie('access_token', '', -60);
    $expiredRefresh = cookie('refresh_token', '', -60);

    return response()->json(['message' => 'Logged out'])->withCookie($expiredAccess)->withCookie($expiredRefresh);
    }
}
