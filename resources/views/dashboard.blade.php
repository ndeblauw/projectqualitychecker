<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <h1 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Add a project</h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                Save GitHub repositories to your account.
            </p>

            <form method="POST" action="{{ route('projects.store') }}" class="mt-6 flex flex-col gap-4">
                @csrf

                <flux:input
                    name="title"
                    :label="__('Project title')"
                    :value="old('title')"
                    type="text"
                    required
                    :placeholder="__('Quality Checker')"
                />

                <flux:input
                    name="github_url"
                    :label="__('GitHub URL')"
                    :value="old('github_url')"
                    type="url"
                    required
                    placeholder="https://github.com/owner/repository"
                />

                <div>
                    <flux:button type="submit" variant="primary">Add project</flux:button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Your projects</h2>

            <div class="mt-4 flex flex-col gap-3">
                @forelse (auth()->user()->projects()->latest()->get() as $project)
                    <a
                        href="{{ route('projects.show', $project) }}"
                        class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-3 text-sm transition hover:border-neutral-300 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:border-neutral-600 dark:hover:bg-neutral-800/60"
                    >
                        <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $project->title }}</span>
                        <span class="text-neutral-500 dark:text-neutral-400">View</span>
                    </a>
                @empty
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">You have no projects yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
