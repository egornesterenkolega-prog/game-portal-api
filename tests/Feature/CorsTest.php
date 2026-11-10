<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CORS: preflight-запрос и заголовки для разрешённого и чужого источника. */
class CorsTest extends TestCase
{
    use RefreshDatabase;

    private const ALLOWED = 'http://localhost:5173';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cors.allowed_origins' => [self::ALLOWED]]);
    }

    private function preflight(string $origin)
    {
        return $this->options('/api/v1/games', [], [
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ]);
    }

    public function test_preflight_from_allowed_origin_is_accepted(): void
    {
        $response = $this->preflight(self::ALLOWED);

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED);
        $this->assertStringContainsString('POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertStringContainsStringIgnoringCase('authorization', $response->headers->get('Access-Control-Allow-Headers'));
    }

    public function test_preflight_from_foreign_origin_is_not_allowed(): void
    {
        $header = $this->preflight('http://evil.example')->headers->get('Access-Control-Allow-Origin');

        // сервер не разрешает чужой источник: в заголовке нет ни его адреса, ни «*»
        // (если разрешён один адрес, возвращается он сам, и браузер блокирует чужой сайт)
        $this->assertNotSame('http://evil.example', $header);
        $this->assertNotSame('*', $header);
    }

    public function test_actual_request_from_allowed_origin_has_cors_header(): void
    {
        $this->getJson('/api/v1/genres', ['Origin' => self::ALLOWED])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED);
    }

    public function test_error_responses_also_have_cors_header(): void
    {
        // иначе браузер скроет от клиента ответ 401 и покажет «CORS error»
        $this->getJson('/api/v1/me', ['Origin' => self::ALLOWED])
            ->assertStatus(401)
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED);
    }

    public function test_wildcard_origin_and_credentials_are_not_used(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertFalse(config('cors.supports_credentials'));

        $this->getJson('/api/v1/genres', ['Origin' => self::ALLOWED])
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }
}
