<?php

namespace App\Enums;

enum ReactionType: string
{
    case Like = 'like';
    case Dislike = 'dislike';

    public function label(): string
    {
        return match ($this) {
            self::Like => 'Лайк',
            self::Dislike => 'Дизлайк',
        };
    }
}
