<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\ListUsersRequest;
use Illuminate\Http\Request;
use App\Interfaces\UserRepositoryInterface;
use App\Traits\ApiResponse;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;

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
        $authUser = $request->user();

        // Only admins can list all users
        if ($authUser->role !== 'admin') {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        $users = $this->userRepository->getAllUsers();
        return $this->successResponse(UserResource::collection($users), 'Users retrieved');
    }

    public function show(Request $request, $id = null)
    {
        // Récupère l'id depuis le paramètre de route ou la query string (flexible pour les tests)
        $id = $id ?? $request->route('id') ?? $request->query('id');

        $user = $this->userRepository->getUserById($id);

        // Authorize viewing this user
        $this->authorize('view', $user);

        return $this->successResponse(new UserResource($user), 'User details');
    }

    /**
     * Update a user's profile. Admins can update any user; users can update their own profile.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $authUser = $request->user();

        // Find target user
        $target = User::findOrFail($id);

        // Authorize
        $this->authorize('update', $target);

        $data = $request->validated();
        if (isset($data['password'])) {
            // hash password
            $data['password'] = \Hash::make($data['password']);
        }

        $this->userRepository->updateUser($id, $data);
        $updated = $this->userRepository->getUserById($id);

        return $this->successResponse(new UserResource($updated), 'Profil mis à jour');
    }
}
