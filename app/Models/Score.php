<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['player_name', 'score', 'difficulty', 'duration_seconds'])]
class Score extends Model
{
    /**
     * The difficulties the arcade cabinet can be played on.
     *
     * @var list<string>
     */
    public const DIFFICULTIES = ['chill', 'classic', 'insane'];

    /**
     * The points needed to win a match.
     */
    public const WINNING_SCORE = 7;

    /**
     * Order scores from best to worst: most points, then fastest win.
     *
     * @param  Builder<Score>  $query
     * @return Builder<Score>
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query
            ->orderByDesc('score')
            ->orderBy('duration_seconds')
            ->orderBy('id');
    }

    /**
     * Determine the position of the given score on the leaderboard.
     */
    public static function rankOf(self $score): int
    {
        return static::query()
            ->where(function (Builder $query) use ($score) {
                $query->where('score', '>', $score->score)
                    ->orWhere(function (Builder $query) use ($score) {
                        $query->where('score', $score->score)
                            ->where('duration_seconds', '<', $score->duration_seconds);
                    });
            })
            ->count() + 1;
    }
}
