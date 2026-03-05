<?php

use App\Models\Project;
use App\Services\GitHub\RepositoryStatistics;
use Illuminate\Support\Carbon;

test('project uses cached repository statistics when latest commit identifier is unchanged', function () {
    $cachedStatistics = [
        'first_commit_date' => '2024-01-01',
        'total_commits' => 12,
        'earliest_commit_time' => '08:00:00',
        'latest_commit_time' => '17:45:00',
        'commits_per_day' => [
            '2024-01-01' => 3,
            '2024-01-02' => 9,
        ],
    ];

    $project = Project::factory()->create([
        'github_url' => 'https://github.com/acme/reporter',
        'repository_statistics' => $cachedStatistics,
        'repository_statistics_latest_commit_identifier' => 'sha-same',
        'repository_statistics_refreshed_at' => Carbon::parse('2026-01-01 10:00:00'),
    ]);

    $github = \Mockery::mock();
    $repoApi = \Mockery::mock();
    $commitsApi = \Mockery::mock();

    $github->shouldReceive('repo')->once()->andReturn($repoApi);
    $repoApi->shouldReceive('commits')->once()->andReturn($commitsApi);
    $commitsApi->shouldReceive('all')->once()->with('acme', 'reporter', [
        'per_page' => 1,
        'page' => 1,
    ])->andReturn([
        ['sha' => 'sha-same'],
    ]);

    app()->instance('github', $github);

    $statistics = $project->fresh()->repositoryStatistics();

    expect($statistics)->toBeInstanceOf(RepositoryStatistics::class)
        ->and($statistics->totalCommits)->toBe(12)
        ->and($statistics->commitsPerDay)->toBe([
            '2024-01-01' => 3,
            '2024-01-02' => 9,
        ]);
});

test('project refreshes and stores repository statistics when latest commit identifier changed', function () {
    $project = Project::factory()->create([
        'github_url' => 'https://github.com/acme/reporter',
        'repository_statistics' => [
            'first_commit_date' => '2024-01-01',
            'total_commits' => 2,
            'earliest_commit_time' => '08:00:00',
            'latest_commit_time' => '09:00:00',
            'commits_per_day' => [
                '2024-01-01' => 2,
            ],
        ],
        'repository_statistics_latest_commit_identifier' => 'sha-old',
    ]);

    $github = \Mockery::mock();
    $repoApi = \Mockery::mock();
    $commitsApi = \Mockery::mock();

    $github->shouldReceive('repo')->twice()->andReturn($repoApi);
    $repoApi->shouldReceive('commits')->twice()->andReturn($commitsApi);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'reporter', [
        'per_page' => 1,
        'page' => 1,
    ])->andReturn([
        ['sha' => 'sha-new'],
    ]);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'reporter', [
        'per_page' => 100,
        'page' => 1,
    ])->andReturn([
        ['sha' => 'sha-new', 'commit' => ['author' => ['date' => '2024-01-03T10:00:00Z']]],
        ['sha' => 'sha-older', 'commit' => ['author' => ['date' => '2024-01-01T09:00:00Z']]],
    ]);

    $commitsApi->shouldReceive('all')->once()->with('acme', 'reporter', [
        'per_page' => 100,
        'page' => 2,
    ])->andReturn([]);

    app()->instance('github', $github);

    $statistics = $project->fresh()->repositoryStatistics();

    expect($statistics)->toBeInstanceOf(RepositoryStatistics::class)
        ->and($statistics->totalCommits)->toBe(2)
        ->and($statistics->firstCommitDate)->toBe('2024-01-01');

    $project->refresh();

    expect($project->repository_statistics_latest_commit_identifier)->toBe('sha-new')
        ->and($project->repository_statistics_refreshed_at)->not->toBeNull()
        ->and($project->repository_statistics)->toBe([
            'first_commit_date' => '2024-01-01',
            'total_commits' => 2,
            'earliest_commit_time' => '09:00:00',
            'latest_commit_time' => '10:00:00',
            'commits_per_day' => [
                '2024-01-01' => 1,
                '2024-01-02' => 0,
                '2024-01-03' => 1,
            ],
        ]);
});
