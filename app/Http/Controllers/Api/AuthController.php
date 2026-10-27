<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->register($request->validated());

        return response()->json(['user' => $this->userData($user), 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->auth->login($request->validated('email'), $request->validated('password'));

        return response()->json(['user' => $this->userData($user), 'token' => $token]);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];
    }
}
