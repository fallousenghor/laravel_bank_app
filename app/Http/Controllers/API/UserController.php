<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\ListUsersRequest;
use Illuminate\Http\Request;
use App\Interfaces\UserRepositoryInterface;
use App\Traits\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(ListUsersRequest $request)
    {
        $users = $this->userRepository->getAllUsers();
        return $this->successResponse(UserResource::collection($users), 'Users retrieved');
    }

    public function show(Request $request, $id = null)
    {
        // Récupère l'id depuis le paramètre de route ou la query string (flexible pour les tests)
        $id = $id ?? $request->route('id') ?? $request->query('id');
        $user = $this->userRepository->getUserById($id);
        return $this->successResponse(new UserResource($user), 'User details');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/find",
     *     tags={"Users"},
     *     summary="Find a user by telephone or NCI",
     *     description="Provide either `tel` (telephone) or `nci` as query parameter to retrieve a single user.",
     *     @OA\Parameter(
     *         name="tel",
     *         in="query",
     *         description="Telephone number of the user (e.g. +111111111)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="nci",
     *         in="query",
     *         description="National identifier (NCI) of the user",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User found"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="id", type="string"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error - missing parameters")
     * )
     *
     * Find a user by telephone number or NCI.
     * Single endpoint that accepts either `tel` or `nci` as query parameters.
     * Example: GET /api/v1/users/find?tel=+111111111
     */
    public function find(Request $request)
    {
        // Enforce admin-only access for this sensitive lookup
        $user = $request->user();
        if (!$user || ($user->role ?? 'client') !== 'admin') {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        $tel = $request->query('tel');
        $nci = $request->query('nci');

        if (empty($tel) && empty($nci)) {
            return $this->errorResponse('Provide either tel or nci as query parameter', 422);
        }

        try {
            $user = $this->userRepository->getUserByTelOrNci($tel, $nci);
            return $this->successResponse(new UserResource($user), 'User found');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
