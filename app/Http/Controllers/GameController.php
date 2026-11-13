<?php

namespace App\Http\Controllers;

use App\Support\Leaderboard;
use Illuminate\Contracts\View\View;

class GameController extends Controller
{
    public function __construct(private readonly Leaderboard $leaderboard) {}

    /**
     * Show the arcade landing page.
     */
    public function home(): View
    {
        return view('home', [
            'topScores' => $this->leaderboard->top(5),
        ]);
    }

    /**
     * Show the full-screen arcade cabinet.
     */
    public function play(): View
    {
        return view('play', [
            'topScores' => $this->leaderboard->top(5),
        ]);
    }
}
