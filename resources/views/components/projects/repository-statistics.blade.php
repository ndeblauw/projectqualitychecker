@props([
    'statistics',
    'project',
])

@php
    $commitsPerDay = $statistics->commitsPerDay;
    $days = array_keys($commitsPerDay);
    $totalDays = count($days);
    $maxCommits = $commitsPerDay === [] ? 0 : max($commitsPerDay);

    $path = trim((string) parse_url((string) $project->github_url, PHP_URL_PATH), '/');
    $segments = explode('/', $path);
    $repositoryLabel = count($segments) >= 2 ? $segments[0].'/'.$segments[1] : $project->title;

    $displayDays = [];

    if ($totalDays > 0) {
        $firstDay = \Carbon\CarbonImmutable::parse($days[0]);
        $latestDay = \Carbon\CarbonImmutable::parse($days[$totalDays - 1]);
        $actualRangeDays = $firstDay->diffInDays($latestDay) + 1;

        if ($actualRangeDays < 28) {
            $startDay = $firstDay->subDays(7);
            $endDay = $startDay->addDays(27);
        } else {
            $startDay = $firstDay;
            $endDay = $latestDay;
        }

        foreach (\Carbon\CarbonPeriod::create($startDay, $endDay) as $date) {
            $day = $date->format('Y-m-d');
            $displayDays[] = [
                'date' => $day,
                'commits' => $commitsPerDay[$day] ?? 0,
                'weekend' => $date->isWeekend(),
            ];
        }
    }

    $displayDayCount = count($displayDays);
@endphp

<flux:card class="w-full border border-neutral-300 bg-white p-4 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 sm:h-36 sm:max-h-36">
    <div class="flex h-full w-full flex-col gap-4 lg:flex-row lg:gap-6">
        <div class="flex w-full flex-col gap-3 lg:w-80 lg:shrink-0">
            <div class="flex items-start justify-between gap-3">
                <dl class="grid grid-cols-3 gap-3 font-mono text-xs leading-4">
                    <div>
                        <dt class="text-[10px] uppercase tracking-[0.12em] text-neutral-500 dark:text-neutral-400">First commit</dt>
                        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100">{{ $statistics->firstCommitDate ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-[0.12em] text-neutral-500 dark:text-neutral-400">Days tracked</dt>
                        <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-100">{{ $totalDays }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-[0.12em] text-neutral-500 dark:text-neutral-400">Total commits</dt>
                        <dd class="text-base">{{ $statistics->totalCommits }}</dd>
                    </div>
                </dl>
            </div>


            <div class="pt-1">
                <form method="GET" action="{{ route('projects.show', $project) }}" x-data="{ loading: false }" x-on:submit="loading = true">
                    <flux:button variant="filled" class="h-8 w-full justify-center border border-neutral-500 text-xs font-medium uppercase tracking-wide dark:border-neutral-500" type="submit" x-bind:disabled="loading">
                        <span x-show="! loading">Refresh statistics</span>
                        <span x-show="loading">Refreshing...</span>
                    </flux:button>
                </form>
            </div>
        </div>

        <div class="min-w-0 flex-1">
            @if ($displayDayCount === 0)
                <div class="flex h-full items-center justify-center border border-dashed border-neutral-400 font-mono text-xs uppercase tracking-wide text-neutral-500 dark:border-neutral-600 dark:text-neutral-400">
                    No commit history available.
                </div>
            @else
                @php
                    $chartHeight = 100;
                    $barWidth = 10;
                    $barGap = 3;
                    $svgWidth = max(200, $displayDayCount * ($barWidth + $barGap));
                @endphp

                <div class="flex h-full flex-col">
                    <div class="min-h-0 flex-1 overflow-hidden border border-neutral-300 dark:border-neutral-700">
                        <svg class="block h-full min-h-full w-full" viewBox="0 0 {{ $svgWidth }} {{ $chartHeight }}" preserveAspectRatio="none" role="img" aria-label="Commits per day chart">
                            @foreach ($displayDays as $index => $dayData)
                                @php
                                    $commitCount = $dayData['commits'];
                                    $barHeight = max(2, (int) round(($commitCount / max(1, $maxCommits)) * $chartHeight));
                                    $x = $index * ($barWidth + $barGap);
                                    $y = $chartHeight - $barHeight;
                                @endphp

                                @if ($dayData['weekend'])
                                    <rect x="{{ $x }}" y="0" width="{{ $barWidth }}" height="{{ $chartHeight }}" class="fill-neutral-200/60 dark:fill-neutral-800/70"></rect>
                                @endif

                                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" class="fill-neutral-900 dark:fill-neutral-100">
                                    <title>{{ $dayData['date'] }}: {{ $commitCount }} commits</title>
                                </rect>
                            @endforeach
                        </svg>
                    </div>
                </div>
            @endif
        </div>
    </div>
</flux:card>
