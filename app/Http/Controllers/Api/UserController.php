<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    /** GET /users/{user} — профиль автора и список его игр. */
    public function show(User $user): UserResource
    {
        return new UserResource($this->users->profile($user));
    }

    /** PATCH /me — изменение имени в своём профиле. */
    public function update(UpdateProfileRequest $request): UserResource
    {
        return new UserResource($this->users->updateName($request->user(), $request->validated()));
    }
}
