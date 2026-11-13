# Neon Pong

An arcade-style Pong game for the browser, built with Laravel 13, Tailwind CSS 4, and a hand-rolled HTML5 Canvas game engine.

Face the CPU on three difficulty levels or play local 2P on one keyboard. Win a CPU match, post your handle, and your fastest 7-point win lands on the hall of fame.

## Features

- **Playable arcade cabinet** — canvas Pong with ball spin, rally speed-up, particle sparks, screen shake, and synthesized Web Audio SFX.
- **Three CPU personalities** — Chill (forgiving), Classic (the reference experience), and Insane (predictive AI).
- **Local 2P** — Player 1 on `W`/`S`, Player 2 on the arrow keys.
- **Pointer/touch support** — drag on your half of the board.
- **Hall of fame** — file-backed ranked scores, ordered by points then fastest match clock.
- **No database required** — sessions, cache, and scores all use local files.
- **Responsive neon UI** — landing page, dedicated play page, leaderboard, and fullscreen mode.

## Stack

- PHP 8.3+ / Laravel 13
- No database required (file sessions, file cache, JSON leaderboard)
- Vite 8 + Tailwind CSS 4 + vanilla JavaScript
- PHPUnit 12

## Getting started

```sh
composer install
cp .env.example .env
php artisan key:generate
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
3. **`.gitignore`** — ignores `public_html/hot` and `public_html/storage`. `public_html/build` is **committed**: assets are built locally (`npm run build`) and the server never runs npm.

Point your web server's document root at `public_html/`. The application code and `.env` stay above the web root.

## No database required

There is no database connection, migration, or `.sqlite` file needed to run the site:

- **Sessions, cache, and queue drivers** are `file`, `file`, and `sync` in `.env.example`. As a safety net, `AppServiceProvider` overrides any lingering `database` drivers in production so a missing SQLite file cannot cause a 500.
- **Leaderboard scores** are stored in `storage/app/leaderboard.json`, managed by `App\Support\Leaderboard`. The file is created automatically on the first submitted score.
- Override the score file location with `LEADERBOARD_PATH` if needed. Ensure `storage/app/` is writable by the web server.

If you later add a database, remove the driver override in `AppServiceProvider` and switch the relevant drivers back in `.env`.

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
  Support/Leaderboard.php                    # file-backed store + ranking
  Support/LeaderboardEntry.php               # one ranked result
config/leaderboard.php                       # score file path
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

The test suite writes scores to a temporary JSON file and disables Vite manifest lookups, so no database or frontend build is required.

## Production notes

```sh
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure `storage/`, `storage/app/`, and `bootstrap/cache/` are writable by the web server, and set `APP_ENV=production`, `APP_DEBUG=false`, and the correct `APP_URL` in `.env`.
