<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /** Профиль автора со списком его игр и жанрами. */
    public function profile(User $user): User
    {
        return $user->load(['games' => fn ($q) => $q->orderByDesc('rating')->with('genres')]);
    }

    public function updateName(User $user, array $data): User
    {
        $user->update(['name' => $data['name']]);

        return $user;
    }
}
