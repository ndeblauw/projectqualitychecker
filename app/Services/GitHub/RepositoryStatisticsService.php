<?php

namespace App\Services\GitHub;

use App\Models\Project;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use InvalidArgumentException;
use Throwable;

class RepositoryStatisticsService
{
    private ?Project $project = null;

    private bool $resolved = false;

    private ?RepositoryStatistics $statistics = null;

    private ?string $latestCommitIdentifier = null;

    public function forProject(Project $project): self
    {
        $service = clone $this;
        $service->project = $project;
        $service->resolved = false;
        $service->statistics = null;
        $service->latestCommitIdentifier = null;

        return $service;
    }

    public function getFirstCommitDate(): ?string
    {
        return $this->getStatistics()->firstCommitDate;
    }

    public function getTotalCommits(): int
    {
        return $this->getStatistics()->totalCommits;
    }

    public function getEarliestCommitTime(): ?string
    {
        return $this->getStatistics()->earliestCommitTime;
    }

    public function getLatestCommitTime(): ?string
    {
        return $this->getStatistics()->latestCommitTime;
    }

    /**
     * @return array<string, int>
     */
    public function getCommitsPerDay(): array
    {
        return $this->getStatistics()->commitsPerDay;
    }

    public function getStatistics(): RepositoryStatistics
    {
        $this->resolve();

        return $this->statistics ?? RepositoryStatistics::empty();
    }

    public function getLatestCommitIdentifier(): ?string
    {
        if ($this->latestCommitIdentifier !== null) {
            return $this->latestCommitIdentifier;
        }

        if (! $this->project instanceof Project) {
            throw new InvalidArgumentException('Project must be configured before requesting repository statistics.');
        }

        [$owner, $repository] = $this->parseRepositoryPath((string) $this->project->github_url);
        $commitsApi = app('github')->repo()->commits();
        $commits = $commitsApi->all($owner, $repository, [
            'per_page' => 1,
            'page' => 1,
        ]);

        if (! is_array($commits) || $commits === []) {
            return null;
        }

        $latestCommitIdentifier = data_get($commits[0], 'sha');

        if (! is_string($latestCommitIdentifier) || $latestCommitIdentifier === '') {
            return null;
        }

        $this->latestCommitIdentifier = $latestCommitIdentifier;

        return $this->latestCommitIdentifier;
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        if (! $this->project instanceof Project) {
            throw new InvalidArgumentException('Project must be configured before requesting repository statistics.');
        }

        [$owner, $repository] = $this->parseRepositoryPath((string) $this->project->github_url);

        $commitDates = [];
        $github = app('github');
        $commitsApi = $github->repo()->commits();
        $page = 1;

        while (true) {
            $commits = $commitsApi->all($owner, $repository, [
                'per_page' => 100,
                'page' => $page,
            ]);

            if (! is_array($commits) || $commits === []) {
                break;
            }

            if ($page === 1) {
                $firstCommitIdentifier = data_get($commits[0], 'sha');

                if (is_string($firstCommitIdentifier) && $firstCommitIdentifier !== '') {
                    $this->latestCommitIdentifier = $firstCommitIdentifier;
                }
            }

            foreach ($commits as $commit) {
                $date = $this->extractCommitDate($commit);

                if ($date !== null) {
                    $commitDates[] = $date;
                }
            }

            $page++;
        }

        if ($commitDates === []) {
            $this->statistics = RepositoryStatistics::empty();
            $this->resolved = true;

            return;
        }

        usort($commitDates, fn (CarbonImmutable $left, CarbonImmutable $right): int => $left->getTimestamp() <=> $right->getTimestamp());

        $earliestCommit = $commitDates[0];
        $latestCommit = $commitDates[array_key_last($commitDates)];

        $commitCountByDate = [];

        foreach ($commitDates as $commitDate) {
            $day = $commitDate->format('Y-m-d');
            $commitCountByDate[$day] = ($commitCountByDate[$day] ?? 0) + 1;
        }

        $commitsPerDay = [];

        foreach (CarbonPeriod::create($earliestCommit->startOfDay(), $latestCommit->startOfDay()) as $date) {
            $day = $date->format('Y-m-d');
            $commitsPerDay[$day] = $commitCountByDate[$day] ?? 0;
        }

        $this->statistics = new RepositoryStatistics(
            firstCommitDate: $earliestCommit->format('Y-m-d'),
            totalCommits: count($commitDates),
            earliestCommitTime: $earliestCommit->utc()->format('H:i:s'),
            latestCommitTime: $latestCommit->utc()->format('H:i:s'),
            commitsPerDay: $commitsPerDay,
        );
        $this->resolved = true;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseRepositoryPath(string $repositoryUrl): array
    {
        $path = trim((string) parse_url($repositoryUrl, PHP_URL_PATH), '/');
        $segments = explode('/', $path);

        if (count($segments) < 2) {
            throw new InvalidArgumentException('The project GitHub URL must contain both owner and repository segments.');
        }

        $owner = $segments[0];
        $repository = preg_replace('/\.git$/', '', $segments[1]);

        if (! is_string($repository) || $repository === '') {
            throw new InvalidArgumentException('The project GitHub URL must contain a valid repository name.');
        }

        return [$owner, $repository];
    }

    private function extractCommitDate(mixed $commit): ?CarbonImmutable
    {
        $dateString = data_get($commit, 'commit.author.date') ?? data_get($commit, 'commit.committer.date');

        if (! is_string($dateString) || $dateString === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($dateString);
        } catch (Throwable) {
            return null;
        }
    }
}
