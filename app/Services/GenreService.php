<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class GenreService
{
    public function all(): Collection
    {
        return Genre::orderBy('name')->get();
    }

    public function create(array $data): Genre
    {
        return Genre::create(['name' => $data['name']]);
    }

    public function update(Genre $genre, array $data): Genre
    {
        $genre->update(['name' => $data['name']]);

        return $genre;
    }

    /** Жанр, назначенный играм, удалить нельзя (restrictOnDelete) — 409. */
    public function delete(Genre $genre): void
    {
        if ($genre->games()->exists()) {
            Log::warning('Попытка удалить жанр, назначенный играм', ['genre_id' => $genre->id]);

            throw new BusinessException('Нельзя удалить жанр, который назначен играм.');
        }

        $genre->delete();
    }
}
