<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Журнал: запросы к API пишутся, а пароли и токены в журнал не попадают. */
class LoggingTest extends TestCase
{
    use RefreshDatabase;

    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        // отдельный файл журнала только для тестов
        $this->logFile = storage_path('logs/testing.log');
        @unlink($this->logFile);
        config([
            'logging.default' => 'testing',
            'logging.channels.testing' => ['driver' => 'single', 'path' => $this->logFile, 'level' => 'debug'],
        ]);
        Log::forgetChannel('testing');
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
        parent::tearDown();
    }

    private function log(): string
    {
        return is_file($this->logFile) ? file_get_contents($this->logFile) : '';
    }

    public function test_api_request_is_logged_with_method_status_and_duration(): void
    {
        $this->getJson('/api/v1/genres')->assertOk();

        $log = $this->log();
        $this->assertStringContainsString('API request', $log);
        $this->assertStringContainsString('"method":"GET"', $log);
        $this->assertStringContainsString('"path":"/api/v1/genres"', $log);
        $this->assertStringContainsString('"status":200', $log);
        $this->assertStringContainsString('duration_ms', $log);
    }

    public function test_authenticated_request_logs_user_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')->assertOk();

        $this->assertStringContainsString('"user_id":'.$user->id, $this->log());
    }

    public function test_client_errors_are_logged_as_warnings(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);

        $this->assertStringContainsString('.WARNING: API request', $this->log());
    }

    public function test_passwords_and_tokens_never_reach_the_log(): void
    {
        User::factory()->create(['email' => 'secret@gameportal.test']);

        // неудачный вход (пароль в теле запроса) и успешный вход (в ответе выдаётся токен)
        $this->postJson('/api/v1/login', ['email' => 'secret@gameportal.test', 'password' => 'TopSecret-Pass-777'])
            ->assertStatus(401);
        $token = $this->postJson('/api/v1/login', ['email' => 'secret@gameportal.test', 'password' => 'password'])
            ->assertOk()->json('token');
        $this->getJson('/api/v1/games?token=abc-query-token&password=TopSecret-Pass-777');

        $log = $this->log();
        $this->assertStringContainsString('Неудачная попытка входа', $log);
        $this->assertStringNotContainsString('TopSecret-Pass-777', $log);
        $this->assertStringNotContainsString('abc-query-token', $log);
        $this->assertStringNotContainsString($token, $log);
        $this->assertStringNotContainsString(explode('|', $token)[1] ?? $token, $log);
    }

    public function test_business_events_are_logged(): void
    {
        config(['portal.max_comments_per_game' => 1]);
        $game = Game::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/games/{$game->id}/comments", ['body' => 'Первый'])->assertStatus(201);
        $this->postJson("/api/v1/games/{$game->id}/comments", ['body' => 'Второй'])->assertStatus(409);

        $log = $this->log();
        $this->assertStringContainsString('.INFO: Добавлен комментарий', $log);
        $this->assertStringContainsString('.WARNING: Превышен лимит комментариев', $log);
    }
}
