<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    /** Блокировка: ставит флаг и удаляет все выданные токены. Администратора заблокировать нельзя. */
    public function block(User $user): User
    {
        if ($user->isAdmin()) {
            abort(403, 'Нельзя заблокировать администратора.');
        }

        DB::transaction(function () use ($user) {
            $user->update(['is_blocked' => true]);
            $user->tokens()->delete();
        });

        return $user;
    }

    public function unblock(User $user): User
    {
        $user->update(['is_blocked' => false]);

        return $user;
    }
}
