<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_database_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('database', 'ok')
            ->assertJsonStructure(['app', 'time', 'database']);
    }

    public function test_unknown_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/unknown-route')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Маршрут не найден.');
    }
}
