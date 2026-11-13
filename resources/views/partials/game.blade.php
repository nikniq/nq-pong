@php
    $difficultyLabels = [
        'chill' => 'Chill',
        'classic' => 'Classic',
        'insane' => 'Insane',
    ];
@endphp

<div class="cabinet"
     data-pong
     data-endpoint="{{ route('scores.store') }}"
     data-leaderboard-url="{{ route('leaderboard') }}">
    <div class="cabinet-topbar">
        <div class="cabinet-lights" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>

        <div class="mode-switch" role="group" aria-label="Game mode">
            <button type="button" data-mode="cpu" class="is-active" aria-pressed="true">1P vs CPU</button>
            <button type="button" data-mode="local" aria-pressed="false">2P local</button>
        </div>

        <div class="cabinet-utilities">
            <button type="button" class="utility-btn" data-sound-toggle aria-pressed="true">SFX on</button>
            <button type="button" class="utility-btn" data-fullscreen>Fullscreen</button>
        </div>
    </div>

    <div class="cabinet-screen">
        <div class="crt">
            <canvas data-pong-canvas width="960" height="600" tabindex="0" aria-label="Pong playfield. Use W and S or the arrow keys to move. Press Space to start or pause.">
                Your browser needs canvas support to run Neon Pong.
            </canvas>

            <div class="hud" aria-hidden="true">
                <div class="hud-score hud-score-left">
                    <span class="hud-label" data-left-label>You</span>
                    <span data-score-left>0</span>
                </div>
                <div class="hud-center">
                    <span class="hud-status" data-match-status>First to 7</span>
                </div>
                <div class="hud-score hud-score-right">
                    <span class="hud-label" data-right-label>CPU</span>
                    <span data-score-right>0</span>
                </div>
            </div>

            <div class="game-overlay" data-overlay>
                <div class="overlay-panel">
                    <p class="overlay-kicker" data-overlay-kicker>Ready player one</p>
                    <h2 class="overlay-title" data-overlay-title>NEON PONG</h2>
                    <p class="overlay-copy" data-overlay-copy>First to 7. The CPU does not blink.</p>

                    <div class="overlay-actions" data-overlay-actions>
                        <button type="button" class="btn btn-primary" data-action="start">Start match</button>
                    </div>

                    <div data-overlay-extra></div>

                    <p class="overlay-footnote">
                        <kbd>W</kbd><kbd>S</kbd> or <kbd>&uarr;</kbd><kbd>&darr;</kbd> to move &middot; <kbd>Space</kbd> to pause
                    </p>
                </div>
            </div>

            <p class="sr-only" aria-live="polite" data-live-status>Game idle. Press Space or activate start match to play.</p>
        </div>
    </div>

    <div class="cabinet-footer">
        <div class="difficulty-switch">
            <span class="switch-label">CPU level</span>
            <div class="chip-group" role="group" aria-label="CPU difficulty" data-difficulty-group>
                <button type="button" class="chip" data-difficulty="chill" aria-pressed="false">Chill</button>
                <button type="button" class="chip is-active" data-difficulty="classic" aria-pressed="true">Classic</button>
                <button type="button" class="chip" data-difficulty="insane" aria-pressed="false">Insane</button>
            </div>
        </div>

        <p class="cabinet-hint">
            <span class="hidden sm:inline">Drag on the board for touch control</span>
            <span class="sm:hidden">Tap and drag to move</span>
        </p>
    </div>
</div>

<template data-score-template>
    <form class="score-form" method="POST" action="{{ route('scores.store') }}" data-score-form>
        @csrf
        <input type="hidden" name="score" value="">
        <input type="hidden" name="difficulty" value="classic">
        <input type="hidden" name="duration_seconds" value="">

        <label class="score-form-field">
            <span>Your handle</span>
            <input type="text" name="player_name" minlength="2" maxlength="24" required
                   autocomplete="nickname" placeholder="PLAYER ONE" spellcheck="false">
        </label>

        <button type="submit" class="btn btn-primary btn-sm">Post my time</button>
        <p class="score-feedback" data-score-feedback role="status"></p>
    </form>
</template>

<noscript>
    <p class="mt-3 text-center text-xs text-pink-300">
        The arcade cabinet needs JavaScript enabled to run.
    </p>
</noscript>
