<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\ListUsersRequest;
use App\Http\Requests\ShowUserRequest;

class UserController extends Controller
{
    public function index(ListUsersRequest $request)
    {
        $query = User::with('comptes');

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('telephone', 'ilike', "%{$search}%");
            });
        }

        $users = $query->get();
        return UserResource::collection($users);
    }

    public function show(\App\Http\Requests\ShowUserRequest $request)
    {
        $id = $request->validated()['id'];
        $user = User::with('comptes')->findOrFail($id);
        return new UserResource($user);
    }
}
