<header class="sticky top-0 z-40 border-b border-white/5 bg-[#05060f]/80 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-3 px-5 py-4 sm:px-8">
        <a href="{{ route('home') }}" class="group flex items-center gap-3" aria-label="Neon Pong — home">
            <span class="brand-mark" aria-hidden="true">
                <span class="brand-paddle brand-paddle-left"></span>
                <span class="brand-ball"></span>
                <span class="brand-paddle brand-paddle-right"></span>
            </span>
            <span class="text-base font-black tracking-[0.22em] text-white">
                NEON<span class="text-cyan-300">PONG</span>
            </span>
        </a>

        <nav class="order-3 flex w-full items-center gap-1 text-sm sm:order-none sm:ml-4 sm:w-auto" aria-label="Main navigation">
            <a href="{{ route('play') }}"
               @class(['nav-link', 'is-active' => request()->routeIs('play')])>
                Play
            </a>
            <a href="{{ route('leaderboard') }}"
               @class(['nav-link', 'is-active' => request()->routeIs('leaderboard')])>
                Hall of fame
            </a>
            <a href="{{ route('home') }}#how-to-play" class="nav-link hidden md:inline-flex">
                How to play
            </a>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <span class="hidden items-center gap-2 font-mono text-[10px] uppercase tracking-[0.28em] text-slate-500 lg:flex">
                <span class="live-dot" aria-hidden="true"></span>
                Arcade open
            </span>
            <a href="{{ route('play') }}" class="btn btn-primary btn-sm">
                Insert coin
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</header>
