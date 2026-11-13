<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScoreRequest;
use App\Support\Leaderboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeaderboardController extends Controller
{
    public function __construct(private readonly Leaderboard $leaderboard) {}

    /**
     * Show the hall of fame.
     */
    public function index(): View
    {
        return view('leaderboard', [
            'scores' => $this->leaderboard->top(Leaderboard::MAX_ENTRIES),
            'totalMatches' => $this->leaderboard->count(),
        ]);
    }

    /**
     * Store a freshly finished match.
     */
    public function store(StoreScoreRequest $request): JsonResponse|RedirectResponse
    {
        $entry = $this->leaderboard->add($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Score locked in. Welcome to the hall of fame!',
                'rank' => $entry->rank,
                'entry' => [
                    'player_name' => $entry->player_name,
                    'score' => $entry->score,
                    'difficulty' => $entry->difficulty,
                    'duration_seconds' => $entry->duration_seconds,
                ],
            ], 201);
        }

        return redirect()
            ->route('leaderboard')
            ->with('status', 'Score locked in. Welcome to the hall of fame!');
    }
}
