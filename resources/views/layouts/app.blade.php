<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#05060f">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        <title>@yield('title', 'Neon Pong — the classic, relit in neon')</title>
        <meta name="description" content="@yield('description', 'Play Pong in your browser. Face the CPU on three difficulties or grab a friend for local 2P, then post your fastest 7-point win to the hall of fame.')">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#05060f] text-slate-100 antialiased selection:bg-cyan-400/30 selection:text-cyan-50">
        <div class="site-bg" aria-hidden="true"></div>

        <div class="relative z-10 flex min-h-screen flex-col">
            @include('partials.header')

            <main class="flex-1">
                @yield('content')
            </main>

            @include('partials.footer')
        </div>
    </body>
</html>
