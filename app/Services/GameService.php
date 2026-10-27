<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GameService
{
    /**
     * Каталог: поиск, фильтры, сортировка, пагинация по 20.
     * По умолчанию сортировка по рейтингу от высокого к низкому.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'rating';
        $direction = $filters['direction'] ?? 'desc';

        return Game::query()
            ->with(['author', 'genres'])
            ->withCount('comments')
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters['author'] ?? null, fn ($q, $v) => $q->whereHas(
                'author', fn ($a) => $a->where('name', 'like', "%{$v}%")
            ))
            ->when($filters['genre_id'] ?? null, fn ($q, $v) => $q->whereHas(
                'genres', fn ($g) => $g->where('genres.id', $v)
            ))
            ->when(isset($filters['min_rating']), fn ($q) => $q->where('rating', '>=', $filters['min_rating']))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    /** Страница игры: автор, жанры, скриншоты, комментарии с авторами. */
    public function show(Game $game): Game
    {
        return $game->load([
            'author',
            'genres',
            'screenshots',
            'comments' => fn ($q) => $q->latest()->with('user'),
        ])->loadCount('comments');
    }

    /** Публикация игры: файлы + запись + жанры + скриншоты, рейтинг = 0. */
    public function create(User $author, array $data, array $screenshots, UploadedFile $file): Game
    {
        return DB::transaction(function () use ($author, $data, $screenshots, $file) {
            $game = Game::create([
                'user_id' => $author->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'price' => $data['price'],
                'file_path' => $file->store('games'),
                'rating' => 0,
            ]);

            $game->genres()->sync($data['genre_ids']);

            foreach ($screenshots as $screenshot) {
                $game->screenshots()->create(['path' => $screenshot->store('screenshots', 'public')]);
            }

            return $game->load(['author', 'genres', 'screenshots']);
        });
    }

    /** Изменение: текстовые поля, жанры; новый файл игры заменяет старый, скриншоты добавляются. */
    public function update(Game $game, array $data, ?array $screenshots, ?UploadedFile $file): Game
    {
        return DB::transaction(function () use ($game, $data, $screenshots, $file) {
            $game->fill(Arr::only($data, ['title', 'description', 'price']));

            if ($file) {
                Storage::delete($game->file_path);
                $game->file_path = $file->store('games');
            }

            $game->save();

            if (isset($data['genre_ids'])) {
                $game->genres()->sync($data['genre_ids']);
            }

            foreach ($screenshots ?? [] as $screenshot) {
                $game->screenshots()->create(['path' => $screenshot->store('screenshots', 'public')]);
            }

            return $game->load(['author', 'genres', 'screenshots']);
        });
    }

    /** Удаление игры и её файлов; связанные записи удаляются каскадом в БД. */
    public function delete(Game $game): void
    {
        $screenshotPaths = $game->screenshots()->pluck('path')->all();
        $filePath = $game->file_path;

        $game->delete();

        Storage::delete($filePath);
        Storage::disk('public')->delete($screenshotPaths);
    }
}
