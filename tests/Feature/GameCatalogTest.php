<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Каталог игр: просмотр, поиск, фильтры, сортировка, пагинация (доступен без токена). */
class GameCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_catalog_with_pagination(): void
    {
        Game::factory()->count(7)->create();

        $this->getJson('/api/v1/games?per_page=5&page=1')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 7);

        $this->getJson('/api/v1/games?per_page=5&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_catalog_search_by_title(): void
    {
        Game::factory()->create(['title' => 'Лунный храм']);
        Game::factory()->create(['title' => 'Ржавый патруль']);

        $this->getJson('/api/v1/games?search=храм')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Лунный храм');
    }

    public function test_catalog_filter_by_genre(): void
    {
        $genre = Genre::factory()->create();
        $inGenre = Game::factory()->create(['title' => 'Жанровая игра']);
        $inGenre->genres()->attach($genre);
        Game::factory()->create(['title' => 'Без жанра']);

        $this->getJson('/api/v1/games?genre_id='.$genre->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Жанровая игра');
    }

    public function test_catalog_filter_by_price_range_and_min_rating(): void
    {
        Game::factory()->create(['title' => 'Дешёвая', 'price' => 50, 'rating' => 80]);
        Game::factory()->create(['title' => 'Средняя', 'price' => 200, 'rating' => 90]);
        Game::factory()->create(['title' => 'Дорогая', 'price' => 500, 'rating' => 95]);

        $this->getJson('/api/v1/games?price_from=100&price_to=300')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Средняя');

        $this->getJson('/api/v1/games?min_rating=90')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_catalog_sorting_by_price(): void
    {
        Game::factory()->create(['title' => 'Вторая', 'price' => 200]);
        Game::factory()->create(['title' => 'Первая', 'price' => 100]);
        Game::factory()->create(['title' => 'Третья', 'price' => 300]);

        $this->getJson('/api/v1/games?sort=price&order=asc')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Первая')
            ->assertJsonPath('data.2.title', 'Третья');

        $this->getJson('/api/v1/games?sort=price&order=desc')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Третья');
    }

    public function test_catalog_rejects_invalid_sort_field(): void
    {
        $this->getJson('/api/v1/games?sort=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_catalog_rejects_too_large_page_size(): void
    {
        $this->getJson('/api/v1/games?per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_show_game_returns_details(): void
    {
        $game = Game::factory()->create(['title' => 'Подробная игра']);

        $this->getJson('/api/v1/games/'.$game->id)
            ->assertOk()
            ->assertJsonPath('data.id', $game->id)
            ->assertJsonPath('data.title', 'Подробная игра')
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'price', 'rating', 'author', 'genres']]);
    }

    public function test_show_missing_game_returns_404(): void
    {
        $this->getJson('/api/v1/games/999999')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Игра не найдена.');
    }
}
