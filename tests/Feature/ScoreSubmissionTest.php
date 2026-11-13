<?php

namespace Tests\Feature;

use App\Models\Score;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
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

    public function test_a_finished_match_can_be_posted_to_the_leaderboard(): void
    {
        $response = $this->postJson(route('scores.store'), $this->payload());

        $response->assertCreated();
        $response->assertJsonPath('rank', 1);
        $response->assertJsonPath('entry.player_name', 'Neo');

        $this->assertDatabaseHas('scores', [
            'player_name' => 'Neo',
            'score' => 7,
            'difficulty' => 'classic',
            'duration_seconds' => 95,
        ]);
    }

    public function test_the_form_fallback_redirects_to_the_leaderboard(): void
    {
        $response = $this->post(route('scores.store'), $this->payload());

        $response->assertRedirect(route('leaderboard'));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('scores', 1);
    }

    public function test_scores_are_ranked_by_points_then_fastest_time(): void
    {
        Score::create(['player_name' => 'Slow Poke', 'score' => 7, 'difficulty' => 'chill', 'duration_seconds' => 180]);
        Score::create(['player_name' => 'Quick Draw', 'score' => 7, 'difficulty' => 'insane', 'duration_seconds' => 62]);
        Score::create(['player_name' => 'Rookie', 'score' => 3, 'difficulty' => 'chill', 'duration_seconds' => 40]);

        $response = $this->get(route('leaderboard'));

        $response->assertOk();
        $response->assertSeeInOrder(['Quick Draw', 'Slow Poke', 'Rookie']);
    }

    public function test_rank_of_a_new_score_is_calculated_correctly(): void
    {
        Score::create(['player_name' => 'Quick Draw', 'score' => 7, 'difficulty' => 'insane', 'duration_seconds' => 62]);
        Score::create(['player_name' => 'Slow Poke', 'score' => 7, 'difficulty' => 'chill', 'duration_seconds' => 180]);

        $response = $this->postJson(route('scores.store'), $this->payload([
            'player_name' => 'Mid Pace',
            'duration_seconds' => 100,
        ]));

        $response->assertCreated();
        $response->assertJsonPath('rank', 2);
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
        $this->assertDatabaseCount('scores', 0);
    }
}
