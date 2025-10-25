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
}
