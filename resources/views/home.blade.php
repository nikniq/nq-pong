@extends('layouts.app')

@section('title', 'Neon Pong — the classic, relit in neon')
@section('description', 'Play Pong in your browser. Face the CPU on Chill, Classic or Insane, or grab a friend for local 2P. First to 7 — then post your fastest win to the hall of fame.')

@section('content')
    <section class="relative overflow-hidden">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-16 pt-12 sm:px-8 lg:grid-cols-[0.85fr_1.15fr] lg:pt-20">
            <div class="max-w-xl">
                <p class="eyebrow">
                    <span class="eyebrow-dot" aria-hidden="true"></span>
                    Cabinet 001 &middot; now playing
                </p>

                <h1 class="mt-6 text-5xl font-black leading-[0.92] tracking-[-0.045em] sm:text-6xl xl:text-7xl">
                    The classic,<br>
                    <span class="text-gradient">relit in neon.</span>
                </h1>

                <p class="mt-6 text-lg leading-relaxed text-slate-400">
                    Two paddles, one ball, and absolutely no excuses. Face the CPU on three
                    difficulty levels or challenge a friend on the same keyboard &mdash;
                    first to seven takes the set.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#cabinet" class="btn btn-primary">
                        Play now
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a href="{{ route('leaderboard') }}" class="btn btn-ghost">Hall of fame</a>
                </div>

                <dl class="mt-10 grid max-w-md grid-cols-3 divide-x divide-white/10 rounded-2xl border border-white/10 bg-white/[0.02] py-4">
                    <div class="px-4">
                        <dt class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Modes</dt>
                        <dd class="mt-1 font-mono text-2xl font-bold text-white">2</dd>
                    </div>
                    <div class="px-4">
                        <dt class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Difficulty</dt>
                        <dd class="mt-1 font-mono text-2xl font-bold text-white">3</dd>
                    </div>
                    <div class="px-4">
                        <dt class="text-[10px] uppercase tracking-[0.2em] text-slate-500">To win</dt>
                        <dd class="mt-1 font-mono text-2xl font-bold text-white">7</dd>
                    </div>
                </dl>

                <p class="mt-5 flex items-center gap-2 text-xs text-slate-500">
                    <span class="live-dot" aria-hidden="true"></span>
                    No download. No login. Just reflexes.
                </p>
            </div>

            <div id="cabinet" class="min-w-0 scroll-mt-28">
                @include('partials.game')
            </div>
        </div>
    </section>

    <section class="border-y border-white/5 bg-white/[0.015]">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 sm:grid-cols-3 sm:px-8">
            <article class="flex gap-4">
                <span class="feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="8" width="3" height="8" rx="1.5"/>
                        <rect x="18" y="5" width="3" height="14" rx="1.5"/>
                        <circle cx="12" cy="12" r="2.2"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-wide text-white">Three CPU personalities</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        Chill rallies, a classic test, or an Insane opponent that predicts the bounce.
                    </p>
                </div>
            </article>

            <article class="flex gap-4">
                <span class="feature-icon feature-icon-pink" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="8" cy="8" r="2.5"/>
                        <circle cx="16" cy="8" r="2.5"/>
                        <path d="M3 19c0-2.8 2.2-5 5-5s5 2.2 5 5M11 19c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-wide text-white">Couch multiplayer</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        One keyboard, two players. P1 on W/S, P2 on the arrow keys. No controllers required.
                    </p>
                </div>
            </article>

            <article class="flex gap-4">
                <span class="feature-icon feature-icon-lime" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z"/>
                        <path d="M7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold tracking-wide text-white">Hall of fame</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        Beat the CPU, then post your handle. The fastest 7-point win sits on top of the board.
                    </p>
                </div>
            </article>
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
        <div class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="min-w-0">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Hall of fame</p>
                        <h2 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">
                            Fastest hands in the arcade
                        </h2>
                    </div>
                    <a href="{{ route('leaderboard') }}" class="text-sm font-semibold text-cyan-300 transition hover:text-cyan-200">
                        View all &rarr;
                    </a>
                </div>

                <div class="panel mt-6 overflow-hidden">
                    @include('partials.score-table', ['scores' => $topScores])
                </div>
            </div>

            <div id="how-to-play" class="panel scroll-mt-28 p-6 sm:p-8">
                <p class="eyebrow">How to play</p>
                <h2 class="mt-3 text-2xl font-black tracking-tight text-white">Two paddles. One rule.</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    Keep the ball alive, put it past your opponent, and reach seven points first.
                    Every paddle hit speeds the ball up.
                </p>

                <ul class="control-list mt-6">
                    <li>
                        <span>1P &mdash; move</span>
                        <span class="control-keys"><kbd>W</kbd><kbd>S</kbd><span class="text-slate-600">or</span><kbd>&uarr;</kbd><kbd>&darr;</kbd></span>
                    </li>
                    <li>
                        <span>2P &mdash; P1</span>
                        <span class="control-keys"><kbd>W</kbd><kbd>S</kbd></span>
                    </li>
                    <li>
                        <span>2P &mdash; P2</span>
                        <span class="control-keys"><kbd>&uarr;</kbd><kbd>&darr;</kbd></span>
                    </li>
                    <li>
                        <span>Pause</span>
                        <span class="control-keys"><kbd>Space</kbd><span class="text-slate-600">or</span><kbd>Esc</kbd></span>
                    </li>
                    <li>
                        <span>Touch</span>
                        <span class="text-xs text-slate-400">Drag on your half of the board</span>
                    </li>
                </ul>

                <div class="mt-6 rounded-xl border border-cyan-400/15 bg-cyan-400/[0.05] p-4 text-xs leading-relaxed text-cyan-100/70">
                    <strong class="font-semibold text-cyan-200">Ranking rule:</strong>
                    only CPU victories are ranked. Points decide the order; the match clock breaks ties.
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 pb-20 sm:px-8">
        <div class="cta-panel">
            <div class="relative z-10 flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <p class="eyebrow">Coin slot is open</p>
                    <h2 class="mt-3 max-w-xl text-3xl font-black tracking-tight text-white sm:text-4xl">
                        Think you have the fastest hands in the arcade?
                    </h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('play') }}" class="btn btn-primary">Start a match</a>
                    <a href="{{ route('leaderboard') }}" class="btn btn-ghost">See the board</a>
                </div>
            </div>
        </div>
    </section>
@endsection
