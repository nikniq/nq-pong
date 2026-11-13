<?php

namespace Tests\Feature;

use App\Support\Leaderboard;
use Tests\TestCase;

class ScoreSubmissionTest extends TestCase
{
    private string $leaderboardPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leaderboardPath = sys_get_temp_dir().'/neon-pong-leaderboard-'.bin2hex(random_bytes(8)).'.json';

        config(['leaderboard.path' => $this->leaderboardPath]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->leaderboardPath)) {
            unlink($this->leaderboardPath);
        }

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'player_name' => 'Neo',
            'score' => 7,
            'difficulty' => 'classic',
            'duration_seconds' => 95,
        ], $overrides);
    }

    private function leaderboard(): Leaderboard
    {
        return app(Leaderboard::class);
    }

    public function test_a_finished_match_can_be_posted_to_the_leaderboard(): void
    {
        $response = $this->postJson(route('scores.store'), $this->payload());

        $response->assertCreated();
        $response->assertJsonPath('rank', 1);
        $response->assertJsonPath('entry.player_name', 'Neo');

        $this->assertFileExists($this->leaderboardPath);
        $this->assertCount(1, $this->leaderboard()->all());
        $this->assertSame('Neo', $this->leaderboard()->all()->first()->player_name);
    }

    public function test_the_form_fallback_redirects_to_the_leaderboard(): void
    {
        $response = $this->post(route('scores.store'), $this->payload());

        $response->assertRedirect(route('leaderboard'));
        $response->assertSessionHas('status');
        $this->assertCount(1, $this->leaderboard()->all());
    }

    public function test_scores_are_ranked_by_points_then_fastest_time(): void
    {
        $board = $this->leaderboard();
        $board->add(['player_name' => 'Slow Poke', 'score' => 7, 'difficulty' => 'chill', 'duration_seconds' => 180]);
        $board->add(['player_name' => 'Quick Draw', 'score' => 7, 'difficulty' => 'insane', 'duration_seconds' => 62]);
        $board->add(['player_name' => 'Rookie', 'score' => 3, 'difficulty' => 'chill', 'duration_seconds' => 40]);

        $response = $this->get(route('leaderboard'));

        $response->assertOk();
        $response->assertSeeInOrder(['Quick Draw', 'Slow Poke', 'Rookie']);
    }

    public function test_rank_of_a_new_score_is_calculated_correctly(): void
    {
        $board = $this->leaderboard();
        $board->add(['player_name' => 'Quick Draw', 'score' => 7, 'difficulty' => 'insane', 'duration_seconds' => 62]);
        $board->add(['player_name' => 'Slow Poke', 'score' => 7, 'difficulty' => 'chill', 'duration_seconds' => 180]);

        $response = $this->postJson(route('scores.store'), $this->payload([
            'player_name' => 'Mid Pace',
            'duration_seconds' => 100,
        ]));

        $response->assertCreated();
        $response->assertJsonPath('rank', 2);
    }

    public function test_the_store_keeps_only_the_best_fifty_entries(): void
    {
        $board = $this->leaderboard();

        for ($index = 1; $index <= 55; $index += 1) {
            $board->add([
                'player_name' => "Player {$index}",
                'score' => 7,
                'difficulty' => 'classic',
                'duration_seconds' => 200 - $index,
            ]);
        }

        $this->assertCount(Leaderboard::MAX_ENTRIES, $board->all());
        $this->assertSame('Player 55', $board->all()->first()->player_name);
    }

    public function test_a_corrupt_store_reads_as_empty(): void
    {
        file_put_contents($this->leaderboardPath, '{not valid json');

        $response = $this->get(route('leaderboard'));

        $response->assertOk();
        $response->assertSee('The wall is empty');
    }

    public function test_invalid_scores_are_rejected(): void
    {
        $response = $this->postJson(route('scores.store'), $this->payload([
            'player_name' => 'A',
            'score' => 9,
            'difficulty' => 'nightmare',
            'duration_seconds' => 0,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['player_name', 'score', 'difficulty', 'duration_seconds']);
        $this->assertCount(0, $this->leaderboard()->all());
    }
}
