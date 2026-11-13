@extends('layouts.app')

@section('title', 'Play Neon Pong — first to 7')
@section('description', 'Jump into the Neon Pong arcade cabinet. Pick a CPU difficulty or local 2P, first to seven wins, and post your fastest time to the hall of fame.')

@section('content')
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="eyebrow">
                    <span class="live-dot" aria-hidden="true"></span>
                    Cabinet 001 &middot; live
                </p>
                <h1 class="mt-3 text-4xl font-black tracking-[-0.04em] sm:text-5xl">Neon Pong</h1>
                <p class="mt-2 text-sm text-slate-400">First to 7. Best time on the board takes the bragging rights.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('leaderboard') }}" class="btn btn-ghost btn-sm">Hall of fame</a>
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">Back to lobby</a>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div>
                @include('partials.game')
            </div>

            <aside class="space-y-4">
                <section class="panel p-5">
                    <h2 class="text-[10px] font-bold uppercase tracking-[0.26em] text-slate-500">Controls</h2>
                    <ul class="control-list mt-4">
                        <li>
                            <span>1P move</span>
                            <span class="control-keys"><kbd>W</kbd><kbd>S</kbd><kbd>&uarr;</kbd><kbd>&darr;</kbd></span>
                        </li>
                        <li>
                            <span>P1 move</span>
                            <span class="control-keys"><kbd>W</kbd><kbd>S</kbd></span>
                        </li>
                        <li>
                            <span>P2 move</span>
                            <span class="control-keys"><kbd>&uarr;</kbd><kbd>&darr;</kbd></span>
                        </li>
                        <li>
                            <span>Pause</span>
                            <span class="control-keys"><kbd>Space</kbd></span>
                        </li>
                        <li>
                            <span>Touch</span>
                            <span class="text-xs text-slate-400">Drag on the board</span>
                        </li>
                    </ul>
                </section>

                <section class="panel p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-[10px] font-bold uppercase tracking-[0.26em] text-slate-500">Top times</h2>
                        <a href="{{ route('leaderboard') }}" class="text-xs font-semibold text-cyan-300 transition hover:text-cyan-200">
                            All &rarr;
                        </a>
                    </div>
                    <div class="mt-4">
                        @include('partials.top-scores', ['scores' => $topScores])
                    </div>
                </section>

                <section class="panel p-5">
                    <h2 class="text-[10px] font-bold uppercase tracking-[0.26em] text-slate-500">Ranking</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-400">
                        Win a CPU match, then post your handle. A 7-point win ranks first;
                        the match clock breaks ties.
                    </p>
                    <ul class="mt-4 space-y-2 text-xs text-slate-500">
                        <li><span class="text-emerald-300">Chill</span> &mdash; wide paddle window, gentle pace</li>
                        <li><span class="text-cyan-300">Classic</span> &mdash; the reference Pong experience</li>
                        <li><span class="text-pink-300">Insane</span> &mdash; predictive AI, almost no mistakes</li>
                    </ul>
                </section>
            </aside>
        </div>
    </section>
@endsection
