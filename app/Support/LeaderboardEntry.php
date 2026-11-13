<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

class LeaderboardEntry
{
    public function __construct(
        public string $id,
        public string $player_name,
        public int $score,
        public string $difficulty,
        public int $duration_seconds,
        public CarbonImmutable $created_at,
        public int $rank = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        try {
            $createdAt = CarbonImmutable::parse($data['created_at'] ?? 'now');
        } catch (Throwable) {
            $createdAt = CarbonImmutable::now();
        }

        return new self(
            id: (string) ($data['id'] ?? uniqid('entry-', true)),
            player_name: (string) ($data['player_name'] ?? 'Unknown'),
            score: (int) ($data['score'] ?? 0),
            difficulty: (string) ($data['difficulty'] ?? 'classic'),
            duration_seconds: (int) ($data['duration_seconds'] ?? 0),
            created_at: $createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'player_name' => $this->player_name,
            'score' => $this->score,
            'difficulty' => $this->difficulty,
            'duration_seconds' => $this->duration_seconds,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
