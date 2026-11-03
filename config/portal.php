<?php

// Настройки бизнес-правил портала (значения можно менять в .env)
return [
    // Правило 1: сколько игр может опубликовать один автор
    'max_games_per_user' => (int) env('PORTAL_MAX_GAMES_PER_USER', 10),

    // Правило 3: сколько комментариев один пользователь может оставить к одной игре
    'max_comments_per_game' => (int) env('PORTAL_MAX_COMMENTS_PER_GAME', 5),
];
