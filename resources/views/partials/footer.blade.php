<footer class="mt-20 border-t border-white/5">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-10 sm:px-8 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <span class="brand-mark brand-mark-sm" aria-hidden="true">
                <span class="brand-paddle brand-paddle-left"></span>
                <span class="brand-ball"></span>
                <span class="brand-paddle brand-paddle-right"></span>
            </span>
            <div>
                <p class="text-sm font-bold tracking-[0.18em] text-white">NEON<span class="text-cyan-300">PONG</span></p>
                <p class="mt-1 text-xs text-slate-500">Two paddles. One ball. Zero excuses.</p>
            </div>
        </div>

        <nav class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500" aria-label="Footer navigation">
            <a href="{{ route('play') }}" class="transition hover:text-cyan-300">Play</a>
            <a href="{{ route('leaderboard') }}" class="transition hover:text-cyan-300">Hall of fame</a>
            <a href="{{ route('home') }}#how-to-play" class="transition hover:text-cyan-300">How to play</a>
            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-slate-600">Built with Laravel</span>
        </nav>
    </div>
</footer>
