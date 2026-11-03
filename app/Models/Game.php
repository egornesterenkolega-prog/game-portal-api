<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    /** Белый список полей сортировки каталога. */
    public const SORTABLE = ['rating', 'created_at', 'price', 'title'];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'file_path',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    // ---- Локальные скоупы для каталога ----

    /** Поиск по части названия (спецсимволы LIKE экранируются). */
    public function scopeSearch(Builder $query, ?string $text): Builder
    {
        return $query->when($text, fn ($q) => $q->where('title', 'like', '%'.addcslashes($text, '%_\\').'%'));
    }

    /** Поиск по части имени автора. */
    public function scopeByAuthor(Builder $query, ?string $name): Builder
    {
        return $query->when($name, fn ($q) => $q->whereHas(
            'author', fn ($a) => $a->where('name', 'like', '%'.addcslashes($name, '%_\\').'%')
        ));
    }

    /** Фильтр по жанру (связь «многие ко многим»). */
    public function scopeInGenre(Builder $query, ?int $genreId): Builder
    {
        return $query->when($genreId, fn ($q) => $q->whereHas(
            'genres', fn ($g) => $g->where('genres.id', $genreId)
        ));
    }

    /** Минимальный рейтинг. */
    public function scopeMinRating(Builder $query, $rating): Builder
    {
        return $query->when($rating !== null, fn ($q) => $q->where('rating', '>=', $rating));
    }

    /** Диапазон цены. */
    public function scopePriceBetween(Builder $query, $from, $to): Builder
    {
        return $query
            ->when($from !== null, fn ($q) => $q->where('price', '>=', $from))
            ->when($to !== null, fn ($q) => $q->where('price', '<=', $to));
    }

    /** Сортировка по полю из белого списка; при неверном значении — по рейтингу. */
    public function scopeSortBy(Builder $query, ?string $sort, ?string $order): Builder
    {
        $column = in_array($sort, self::SORTABLE, true) ? $sort : 'rating';
        $direction = strtolower((string) $order) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction)->orderByDesc('id');
    }

    /** Автор игры (внешний ключ user_id). */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Жанры игры (таблица game_genre). */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(Screenshot::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /** Пользователи, оценившие игру (через таблицу reactions). */
    public function reactedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reactions')
            ->withPivot('type')
            ->withTimestamps();
    }
}
