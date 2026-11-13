@extends('layouts.app')

@section('title', 'Hall of fame — Neon Pong')
@section('description', 'The fastest 7-point wins against the Neon Pong CPU. Ranked by points and match time, topped by the sharpest hands in the arcade.')

@section('content')
    <section class="mx-auto max-w-5xl px-5 py-12 sm:px-8">
        <p class="eyebrow">
            <span class="eyebrow-dot" aria-hidden="true"></span>
            Hall of fame
        </p>

        <div class="mt-5 flex flex-wrap items-end justify-between gap-6">
            <div>
                <h1 class="max-w-2xl text-4xl font-black leading-[0.98] tracking-[-0.04em] sm:text-6xl">
                    Fastest hands in the arcade.
                </h1>
                <p class="mt-5 max-w-2xl text-sm leading-relaxed text-slate-400 sm:text-base">
                    Every CPU victory is ranked by points, then by match time. The board keeps the
                    top 50 &mdash; the rest are lost to the arcade floor.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.02] px-5 py-4">
                <span class="block font-mono text-2xl font-bold text-white">{{ number_format($totalMatches) }}</span>
                <span class="mt-1 block text-[10px] uppercase tracking-[0.22em] text-slate-500">Ranked wins</span>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-8 flex items-center gap-3 rounded-xl border border-emerald-400/25 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200" role="status">
                <span class="h-2 w-2 rounded-full bg-emerald-300" aria-hidden="true"></span>
                {{ session('status') }}
            </div>
        @endif

        <div class="panel mt-10 overflow-hidden p-1 sm:p-3">
            @include('partials.score-table', [
                'scores' => $scores,
                'showDate' => true,
                'emptyMessage' => 'The wall is empty. Win a CPU match and post the first time.',
            ])
        </div>

        <div class="mt-10 flex flex-col items-start justify-between gap-5 rounded-2xl border border-white/10 bg-white/[0.02] p-6 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-lg font-bold text-white">Think you can beat that?</h2>
                <p class="mt-1 text-sm text-slate-400">Only CPU wins make the board. Faster equals higher.</p>
            </div>
            <a href="{{ route('play') }}" class="btn btn-primary">Claim a spot</a>
        </div>
    </section>
@endsection
