<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    /** Изменять игру может только её автор (ФТ-4). */
    public function update(User $user, Game $game): bool
    {
        return $user->id === $game->user_id;
    }

    /** Удалять игру может автор или администратор (ФТ-4, ФТ-14). */
    public function delete(User $user, Game $game): bool
    {
        return $user->isAdmin() || $user->id === $game->user_id;
    }
}
