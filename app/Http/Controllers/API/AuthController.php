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
        $clientId = config('auth.passport.client_id');
        $clientSecret = config('auth.passport.client_secret');

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

        // Dispatch internal request to Passport token endpoint to avoid external HTTP timeout
        $tokenRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $request->input('email'),
            'password' => $request->input('password'),
            'scope' => $scope,
        ], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $tokenResponse = app()->handle($tokenRequest);

        $status = $tokenResponse->getStatusCode();
        $data = json_decode($tokenResponse->getContent(), true);
        // Log token endpoint response for debugging if it failed
        if ($status >= 400) {
            try {
                \Log::error('Passport token endpoint returned error', ['status' => $status, 'body' => $tokenResponse->getContent()]);
            } catch (\Throwable $e) {
                // ignore logging failures
            }

            // If client credentials from .env appear to be invalid, try to fallback
            // to reading the password client from the database and retry once.
            $retryWithDbClient = false;
            if (is_array($data) && isset($data['error']) && in_array($data['error'], ['invalid_client', 'invalid_grant', 'unauthorized_client'])) {
                $retryWithDbClient = true;
            }

            if ($retryWithDbClient) {
                try {
                    $dbClient = \DB::table('oauth_clients')->where('password_client', 1)->first();
                    if ($dbClient && !empty($dbClient->id) && !empty($dbClient->secret)) {
                        \Log::warning('Retrying token request using oauth client credentials from database.');
                        $clientId = $dbClient->id;
                        $clientSecret = $dbClient->secret;

                        $tokenRequestRetry = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
                            'grant_type' => 'password',
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'username' => $request->input('email'),
                            'password' => $request->input('password'),
                            'scope' => $scope,
                        ], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

                        $tokenResponse = app()->handle($tokenRequestRetry);
                        $status = $tokenResponse->getStatusCode();
                        $data = json_decode($tokenResponse->getContent(), true);
                        if ($status < 400) {
                            \Log::info('Token request retry succeeded using DB client.');
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error('Failed retrying token request with DB client', ['error' => $e->getMessage()]);
                }
            }
                // If the authorization server doesn't support password grant (common on some Passport setups),
                // fall back to issuing a personal access token using Passport's PersonalAccessTokenFactory.
                if (isset($data['error']) && $data['error'] === 'unsupported_grant_type') {
                    // Create a personal access token as a fallback
                    $abilities = [];
                    if ($scope && $scope !== '*') {
                        $abilities = array_filter(explode(' ', $scope));
                    }

                    $tokenResult = $user->createToken('fallback_token', $abilities);
                    $personalAccessToken = $tokenResult->accessToken ?? null;

                    $data = [
                        'access_token' => $personalAccessToken,
                        'token_type' => 'Bearer',
                        'expires_in' => 60 * 24 * 30, // 30 days assumed for personal tokens
                        'user' => $user->makeHidden(['password', 'remember_token']),
                        'note' => 'Issued personal access token as password grant is not supported on this server',
                    ];

                    // Store access token in cookie
                    $accessCookie = cookie('access_token', $personalAccessToken, 60 * 24 * 30, '/', null, config('app.env') !== 'local', true, false, 'Strict');

                    return response()->json($data)->withCookie($accessCookie);
                }

                return response()->json(['message' => 'Failed to issue token', 'details' => $data], $status);
        }

        // Attach user info to the response so clients can know the role/scopes without
        // decoding the token. (We also keep the raw tokens in the response body.)
        $data['user'] = $user->makeHidden(['password', 'remember_token']);

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

        $clientId = config('auth.passport.client_id');
        $clientSecret = config('auth.passport.client_secret');

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
