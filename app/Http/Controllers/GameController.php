<?php

namespace App\Http\Controllers;

use App\Models\Score;
use Illuminate\Contracts\View\View;

class GameController extends Controller
{
    /**
     * Show the arcade landing page.
     */
    public function home(): View
    {
        return view('home', [
            'topScores' => Score::ranked()->limit(5)->get(),
        ]);
    }

    /**
     * Show the full-screen arcade cabinet.
     */
    public function play(): View
    {
        return view('play', [
            'topScores' => Score::ranked()->limit(5)->get(),
        ]);
    }
}
