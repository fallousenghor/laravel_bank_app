<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('comptes')->get();
        return response()->json($users);
    }

    public function show(\App\Http\Requests\ShowUserRequest $request)
    {
        $id = $request->validated()['id'];
        $user = User::with('comptes')->findOrFail($id);
        return response()->json($user);
    }
}
