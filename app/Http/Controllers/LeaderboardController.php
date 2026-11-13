<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScoreRequest;
use App\Models\Score;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeaderboardController extends Controller
{
    /**
     * Show the hall of fame.
     */
    public function index(): View
    {
        return view('leaderboard', [
            'scores' => Score::ranked()->limit(50)->get(),
            'totalMatches' => Score::count(),
        ]);
    }

    /**
     * Store a freshly finished match.
     */
    public function store(StoreScoreRequest $request): JsonResponse|RedirectResponse
    {
        $score = Score::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Score locked in. Welcome to the hall of fame!',
                'rank' => Score::rankOf($score),
                'entry' => [
                    'player_name' => $score->player_name,
                    'score' => $score->score,
                    'difficulty' => $score->difficulty,
                    'duration_seconds' => $score->duration_seconds,
                ],
            ], 201);
        }

        return redirect()
            ->route('leaderboard')
            ->with('status', 'Score locked in. Welcome to the hall of fame!');
    }
}
