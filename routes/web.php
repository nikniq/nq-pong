<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'home'])->name('home');
Route::get('/play', [GameController::class, 'play'])->name('play');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::post('/scores', [LeaderboardController::class, 'store'])->name('scores.store');
