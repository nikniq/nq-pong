# Neon Pong

An arcade-style Pong game for the browser, built with Laravel 13, Tailwind CSS 4, and a hand-rolled HTML5 Canvas game engine.

Face the CPU on three difficulty levels or play local 2P on one keyboard. Win a CPU match, post your handle, and your fastest 7-point win lands on the hall of fame.

## Features

- **Playable arcade cabinet** — canvas Pong with ball spin, rally speed-up, particle sparks, screen shake, and synthesized Web Audio SFX.
- **Three CPU personalities** — Chill (forgiving), Classic (the reference experience), and Insane (predictive AI).
- **Local 2P** — Player 1 on `W`/`S`, Player 2 on the arrow keys.
- **Pointer/touch support** — drag on your half of the board.
- **Hall of fame** — server-ranked scores, ordered by points then fastest match clock.
- **Responsive neon UI** — landing page, dedicated play page, leaderboard, and fullscreen mode.

## Stack

- PHP 8.3+ / Laravel 13
- SQLite by default (any Laravel-supported database works)
- Vite 8 + Tailwind CSS 4 + vanilla JavaScript
- PHPUnit 12

## Getting started

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

Open <http://localhost:8000>.

Or let Composer run the whole setup:

```sh
composer setup
```

For development with hot module replacement, run the Laravel server and Vite together:

```sh
composer dev
```

## The `public_html` public directory

This project intentionally serves from **`public_html/`** instead of Laravel's default `public/`, so the repository can be dropped onto shared hosting that expects that folder name.

The rename is wired up in three places:

1. **`bootstrap/app.php`** — calls `$app->usePublicPath($app->basePath('public_html'))`, which updates `public_path()`, `php artisan serve`, storage links, and the Vite manifest lookup.
2. **`vite.config.js`** — sets `publicDirectory: 'public_html'`, `buildDirectory: 'build'`, and `hotFile: 'public_html/hot'`, so dev and production assets are emitted to `public_html/build`.
3. **`.gitignore`** — ignores `public_html/build`, `public_html/hot`, and `public_html/storage`.

Point your web server's document root at `public_html/`. The application code and `.env` stay above the web root.

## Game controls

| Action | Keys |
| --- | --- |
| 1P move | `W` / `S` or `↑` / `↓` |
| 2P — Player 1 | `W` / `S` |
| 2P — Player 2 | `↑` / `↓` |
| Pause / resume | `Space` or `Esc` |
| Start / restart | `Space`, `Enter`, or the on-screen buttons |
| Touch | Drag on the playfield |

Matches are first to **7 points**. Only CPU victories are eligible for the leaderboard; at equal scores, the faster match ranks higher. The top 50 results are displayed.

## Routes

| Method | URI | Description |
| --- | --- | --- |
| `GET` | `/` | Landing page with the playable cabinet |
| `GET` | `/play` | Dedicated full-width arcade view |
| `GET` | `/leaderboard` | Top 50 ranked wins |
| `POST` | `/scores` | Store a finished CPU victory (JSON or redirect) |

## Project layout

```text
app/
  Http/Controllers/GameController.php        # landing + play pages
  Http/Controllers/LeaderboardController.php # board + score submission
  Http/Requests/StoreScoreRequest.php        # score validation
  Models/Score.php                           # ranking scopes and helpers
database/migrations/..._create_scores_table.php
public_html/                                 # web root (built assets live here)
resources/
  css/app.css                                # Tailwind + arcade components
  js/app.js                                  # Vite entry
  js/pong.js                                 # canvas game engine
  views/
    home.blade.php
    play.blade.php
    leaderboard.blade.php
    layouts/app.blade.php
    partials/                                # header, footer, cabinet, score tables
routes/web.php
tests/
  Feature/SiteTest.php
  Feature/ScoreSubmissionTest.php
```

## Testing

```sh
php artisan test
```

The test suite uses in-memory SQLite and disables Vite manifest lookups, so it runs without a frontend build.

## Production notes

```sh
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure `storage/` and `bootstrap/cache/` are writable by the web server, and set `APP_ENV=production`, `APP_DEBUG=false`, and the correct `APP_URL` in `.env`.
