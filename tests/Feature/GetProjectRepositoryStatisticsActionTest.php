<?php

use App\Models\Project;
use App\Services\GitHub\RepositoryStatistics;
use App\Services\GitHub\RepositoryStatisticsService;

test('repository statistics service returns commit statistics with missing days filled as zero', function () {
    $github = Mockery::mock();
    $repoApi = Mockery::mock();
    $commitsApi = Mockery::mock();

    $github->shouldReceive('repo')->once()->andReturn($repoApi);
    $repoApi->shouldReceive('commits')->once()->andReturn($commitsApi);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'quality-checker', [
        'per_page' => 100,
        'page' => 1,
    ])->andReturn([
        ['commit' => ['author' => ['date' => '2024-01-03T16:00:00Z']]],
        ['commit' => ['author' => ['date' => '2024-01-01T09:00:00Z']]],
    ]);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'quality-checker', [
        'per_page' => 100,
        'page' => 2,
    ])->andReturn([
        ['commit' => ['author' => ['date' => '2024-01-03T10:30:00Z']]],
        ['commit' => ['author' => ['date' => '2024-01-05T12:00:00Z']]],
    ]);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'quality-checker', [
        'per_page' => 100,
        'page' => 3,
    ])->andReturn([]);

    app()->instance('github', $github);

    $project = new Project([
        'github_url' => 'https://github.com/acme/quality-checker',
    ]);

    $statisticsService = app(RepositoryStatisticsService::class)->forProject($project);

    expect($statisticsService->getStatistics())->toBeInstanceOf(RepositoryStatistics::class)
        ->and($statisticsService->getFirstCommitDate())->toBe('2024-01-01')
        ->and($statisticsService->getTotalCommits())->toBe(4)
        ->and($statisticsService->getEarliestCommitTime())->toBe('09:00:00')
        ->and($statisticsService->getLatestCommitTime())->toBe('12:00:00')
        ->and($statisticsService->getCommitsPerDay())->toBe([
            '2024-01-01' => 1,
            '2024-01-02' => 0,
            '2024-01-03' => 2,
            '2024-01-04' => 0,
            '2024-01-05' => 1,
        ]);
});

test('repository statistics service returns empty statistics when the repository has no commits', function () {
    $github = Mockery::mock();
    $repoApi = Mockery::mock();
    $commitsApi = Mockery::mock();

    $github->shouldReceive('repo')->once()->andReturn($repoApi);
    $repoApi->shouldReceive('commits')->once()->andReturn($commitsApi);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'empty-repository', [
        'per_page' => 100,
        'page' => 1,
    ])->andReturn([]);

    app()->instance('github', $github);

    $project = new Project([
        'github_url' => 'https://github.com/acme/empty-repository',
    ]);

    $statisticsService = app(RepositoryStatisticsService::class)->forProject($project);

    expect($statisticsService->getStatistics())->toBeInstanceOf(RepositoryStatistics::class)
        ->and($statisticsService->getFirstCommitDate())->toBeNull()
        ->and($statisticsService->getTotalCommits())->toBe(0)
        ->and($statisticsService->getEarliestCommitTime())->toBeNull()
        ->and($statisticsService->getLatestCommitTime())->toBeNull()
        ->and($statisticsService->getCommitsPerDay())->toBe([]);
});
