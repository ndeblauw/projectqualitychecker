<?php

namespace App\Services\GitHub;

final readonly class RepositoryStatistics
{
    /**
     * @param  array<string, int>  $commitsPerDay
     */
    public function __construct(
        public ?string $firstCommitDate,
        public int $totalCommits,
        public ?string $earliestCommitTime,
        public ?string $latestCommitTime,
        public array $commitsPerDay,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $commitsPerDay = $payload['commits_per_day'] ?? [];

        return new self(
            firstCommitDate: is_string($payload['first_commit_date'] ?? null) ? $payload['first_commit_date'] : null,
            totalCommits: (int) ($payload['total_commits'] ?? 0),
            earliestCommitTime: is_string($payload['earliest_commit_time'] ?? null) ? $payload['earliest_commit_time'] : null,
            latestCommitTime: is_string($payload['latest_commit_time'] ?? null) ? $payload['latest_commit_time'] : null,
            commitsPerDay: is_array($commitsPerDay) ? array_map(fn (mixed $count): int => (int) $count, $commitsPerDay) : [],
        );
    }

    /**
     * @return array{first_commit_date: ?string, total_commits: int, earliest_commit_time: ?string, latest_commit_time: ?string, commits_per_day: array<string, int>}
     */
    public function toArray(): array
    {
        return [
            'first_commit_date' => $this->firstCommitDate,
            'total_commits' => $this->totalCommits,
            'earliest_commit_time' => $this->earliestCommitTime,
            'latest_commit_time' => $this->latestCommitTime,
            'commits_per_day' => $this->commitsPerDay,
        ];
    }

    public static function empty(): self
    {
        return new self(null, 0, null, null, []);
    }
}
