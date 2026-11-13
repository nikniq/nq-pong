<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A tiny file-backed leaderboard. The arcade intentionally does not need a
 * database: scores live in a JSON file under storage/app.
 */
class Leaderboard
{
    public const WINNING_SCORE = 7;

    /**
     * @var list<string>
     */
    public const DIFFICULTIES = ['chill', 'classic', 'insane'];

    public const MAX_ENTRIES = 50;

    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: (string) config('leaderboard.path', storage_path('app/leaderboard.json'));
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return Collection<int, LeaderboardEntry>
     */
    public function all(): Collection
    {
        return $this->entriesFrom($this->read());
    }

    /**
     * @return Collection<int, LeaderboardEntry>
     */
    public function top(int $limit = self::MAX_ENTRIES): Collection
    {
        return $this->all()->take($limit)->values();
    }

    public function count(): int
    {
        return count($this->read());
    }

    /**
     * Append a result, keep the store sorted, and return the entry with its rank.
     *
     * @param  array{player_name: string, score: int|string, difficulty: string, duration_seconds: int|string}  $data
     */
    public function add(array $data): LeaderboardEntry
    {
        $this->ensureDirectoryExists();

        $handle = fopen($this->path, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to open the leaderboard store at [{$this->path}].");
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException("Unable to lock the leaderboard store at [{$this->path}].");
            }

            $contents = stream_get_contents($handle);
            $entries = $this->decode($contents === false ? '' : $contents);

            $entry = new LeaderboardEntry(
                id: (string) Str::uuid(),
                player_name: (string) $data['player_name'],
                score: (int) $data['score'],
                difficulty: (string) $data['difficulty'],
                duration_seconds: (int) $data['duration_seconds'],
                created_at: CarbonImmutable::now(),
            );

            $entries[] = $entry->toArray();
            $entries = $this->sorted($entries);
            $entry->rank = $this->rankIn($entries, $entry->id);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $this->encode(array_slice($entries, 0, self::MAX_ENTRIES)));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return $entry;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            return [];
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                return [];
            }

            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return $this->decode($contents === false ? '' : $contents);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function decode(string $contents): array
    {
        if (trim($contents) === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            $decoded,
            fn (mixed $entry): bool => is_array($entry)
                && isset($entry['player_name'], $entry['score'], $entry['difficulty'], $entry['duration_seconds']),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    protected function encode(array $entries): string
    {
        return json_encode(
            array_values($entries),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;
    }

    /**
     * Sort by score descending, then duration ascending, then earliest created.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    protected function sorted(array $entries): array
    {
        usort($entries, fn (array $a, array $b): int => [
            (int) $b['score'],
            (int) $a['duration_seconds'],
            (string) ($a['created_at'] ?? ''),
            (string) ($a['id'] ?? ''),
        ] <=> [
            (int) $a['score'],
            (int) $b['duration_seconds'],
            (string) ($b['created_at'] ?? ''),
            (string) ($b['id'] ?? ''),
        ]);

        return $entries;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return Collection<int, LeaderboardEntry>
     */
    protected function entriesFrom(array $entries): Collection
    {
        return collect($this->sorted($entries))
            ->values()
            ->map(function (array $entry, int $index): LeaderboardEntry {
                $item = LeaderboardEntry::fromArray($entry);
                $item->rank = $index + 1;

                return $item;
            });
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    protected function rankIn(array $entries, string $id): int
    {
        foreach ($entries as $index => $entry) {
            if (($entry['id'] ?? null) === $id) {
                return $index + 1;
            }
        }

        return count($entries);
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
