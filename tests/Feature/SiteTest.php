<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_loads_the_arcade(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('NEON');
        $response->assertSee('data-pong-canvas', false);
        $response->assertSee('Hall of fame');
    }

    public function test_the_play_page_loads_the_cabinet(): void
    {
        $response = $this->get('/play');

        $response->assertOk();
        $response->assertSee('Cabinet 001');
        $response->assertSee('data-pong-canvas', false);
    }

    public function test_the_leaderboard_loads_even_when_empty(): void
    {
        $response = $this->get('/leaderboard');

        $response->assertOk();
        $response->assertSee('Hall of fame');
        $response->assertSee('The wall is empty');
    }
}
