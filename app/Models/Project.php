<?php

namespace App\Models;

use App\Services\GitHub\RepositoryStatistics;
use App\Services\GitHub\RepositoryStatisticsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Throwable;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'github_url',
        'repository_statistics',
        'repository_statistics_latest_commit_identifier',
        'repository_statistics_refreshed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repository_statistics' => 'array',
            'repository_statistics_refreshed_at' => 'datetime',
        ];
    }

    public function repositoryStatistics(bool $forceRefresh = false): RepositoryStatistics
    {
        /** @var RepositoryStatisticsService $repositoryStatisticsService */
        $repositoryStatisticsService = app(RepositoryStatisticsService::class);
        $statisticsService = $repositoryStatisticsService->forProject($this);

        if (! $forceRefresh) {
            $cachedStatistics = $this->cachedRepositoryStatistics();

            if ($cachedStatistics !== null) {
                try {
                    $latestCommitIdentifier = $statisticsService->getLatestCommitIdentifier();

                    if ($latestCommitIdentifier === $this->repository_statistics_latest_commit_identifier) {
                        return $cachedStatistics;
                    }
                } catch (Throwable) {
                    return $cachedStatistics;
                }
            }
        }

        $statistics = $statisticsService->getStatistics();
        $latestCommitIdentifier = $statisticsService->getLatestCommitIdentifier();

        $this->forceFill([
            'repository_statistics' => $statistics->toArray(),
            'repository_statistics_latest_commit_identifier' => $latestCommitIdentifier,
            'repository_statistics_refreshed_at' => Carbon::now(),
        ])->save();

        return $statistics;
    }

    private function cachedRepositoryStatistics(): ?RepositoryStatistics
    {
        if (! is_array($this->repository_statistics)) {
            return null;
        }

        return RepositoryStatistics::fromArray($this->repository_statistics);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
