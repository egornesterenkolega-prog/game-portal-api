<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GameService
{
    /**
     * Каталог: поиск, фильтры, сортировка из белого списка, пагинация.
     * По умолчанию — по рейтингу от высокого к низкому, 20 на странице.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $order = $filters['order'] ?? $filters['direction'] ?? 'desc';

        return Game::query()
            ->with(['author', 'genres'])
            ->withCount('comments')
            ->search($filters['search'] ?? null)
            ->byAuthor($filters['author'] ?? null)
            ->inGenre($filters['genre_id'] ?? null)
            ->minRating($filters['min_rating'] ?? null)
            ->priceBetween($filters['price_from'] ?? null, $filters['price_to'] ?? null)
            ->sortBy($filters['sort'] ?? 'rating', $order)
            ->paginate($filters['per_page'] ?? 20)
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

    /**
     * Ключевой сценарий: публикация игры.
     *
     * Бизнес-правило: у автора не более N игр (config portal.max_games_per_user).
     * Всё выполняется в одной транзакции; строка автора блокируется (lockForUpdate),
     * чтобы параллельные запросы не обошли лимит. При любой ошибке запись в БД
     * откатывается, а уже сохранённые файлы удаляются.
     */
    public function create(User $author, array $data, array $screenshots, UploadedFile $file): Game
    {
        $localFiles = [];
        $publicFiles = [];

        try {
            return DB::transaction(function () use ($author, $data, $screenshots, $file, &$localFiles, &$publicFiles) {
                User::whereKey($author->id)->lockForUpdate()->first();

                $limit = (int) config('portal.max_games_per_user');
                if (Game::where('user_id', $author->id)->count() >= $limit) {
                    Log::warning('Превышен лимит игр автора', ['user_id' => $author->id, 'limit' => $limit]);

                    throw new BusinessException("Достигнут лимит: автор может опубликовать не более {$limit} игр.");
                }

                $filePath = $file->store('games');
                $localFiles[] = $filePath;

                $game = Game::create([
                    'user_id' => $author->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'file_path' => $filePath,
                    'rating' => 0,
                ]);

                $game->genres()->sync($data['genre_ids']);

                foreach ($screenshots as $screenshot) {
                    $path = $screenshot->store('screenshots', 'public');
                    $publicFiles[] = $path;
                    $game->screenshots()->create(['path' => $path]);
                }

                Log::info('Игра опубликована', ['game_id' => $game->id, 'user_id' => $author->id]);

                return $game->load(['author', 'genres', 'screenshots']);
            });
        } catch (\Throwable $e) {
            if (! $e instanceof BusinessException) {
                Log::error('Не удалось опубликовать игру', ['user_id' => $author->id, 'error' => $e->getMessage()]);
            }

            Storage::delete($localFiles);
            Storage::disk('public')->delete($publicFiles);

            throw $e;
        }
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
        Log::info('Игра удалена', ['game_id' => $game->id]);

        Storage::delete($filePath);
        Storage::disk('public')->delete($screenshotPaths);
    }
}
