<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /** Выражение «год-месяц» для разных СУБД (MySQL — рабочая БД, SQLite — тесты). */
    private function monthExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    /** Сводная статистика портала (withCount, selectRaw, groupBy). */
    public function overview(): array
    {
        $price = Game::selectRaw(
            'COUNT(*) AS total,
             SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) AS free,
             AVG(price) AS avg_price,
             MAX(price) AS max_price,
             AVG(rating) AS avg_rating'
        )->first();

        return [
            'totals' => [
                'users' => User::count(),
                'games' => Game::count(),
                'comments' => Comment::count(),
                'reactions' => Reaction::count(),
            ],
            'games' => [
                'free' => (int) $price->free,
                'paid' => (int) $price->total - (int) $price->free,
                'avg_price' => round((float) $price->avg_price, 2),
                'max_price' => (float) $price->max_price,
                'avg_rating' => round((float) $price->avg_rating, 2),
            ],
            // игры по жанрам (withCount по связи «многие ко многим»)
            'genres' => Genre::withCount('games')
                ->orderByDesc('games_count')->orderBy('name')
                ->get()
                ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'games_count' => $g->games_count]),
            // самые оценённые игры
            'top_games' => Game::withCount(['reactions', 'comments'])
                ->orderByDesc('reactions_count')->orderByDesc('rating')->limit(5)
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id, 'title' => $g->title, 'rating' => (float) $g->rating,
                    'reactions_count' => $g->reactions_count, 'comments_count' => $g->comments_count,
                ]),
            // самые активные авторы
            'top_authors' => User::withCount('games')
                ->has('games')
                ->orderByDesc('games_count')->orderBy('name')->limit(5)
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'games_count' => $u->games_count]),
            // публикации по месяцам (groupBy)
            'games_by_month' => Game::selectRaw($this->monthExpression().' AS month, COUNT(*) AS total')
                ->groupBy('month')->orderBy('month')
                ->get()
                ->map(fn ($r) => ['month' => $r->month, 'total' => (int) $r->total]),
        ];
    }
}
