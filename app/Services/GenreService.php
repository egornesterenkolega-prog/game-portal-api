<?php

namespace App\Services;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;

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
            abort(409, 'Нельзя удалить жанр, который назначен играм.');
        }

        $genre->delete();
    }
}
