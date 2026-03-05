<x-layouts::app :title="$project->title">
    <div class="mx-auto flex h-full w-full max-w-4xl flex-1 flex-col gap-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">{{ $project->title }}</h1>
                    <a
                        href="{{ $project->github_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-1 block text-xs text-neutral-500 underline underline-offset-4 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
                    >
                        {{ $project->github_url }}
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:modal.trigger name="confirm-project-deletion">
                        <flux:button variant="danger" class="h-8 px-2" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-project-deletion')" aria-label="Delete project">
                            <flux:icon.trash class="size-4" />
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:modal.trigger name="edit-project-details">
                        <flux:button variant="primary" class="h-8 px-3 text-xs" x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-project-details')">
                            Edit
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400">
            This section will be added later.
        </div>
    </div>

    <flux:modal name="confirm-project-deletion" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete project?</flux:heading>
                <flux:subheading>This action cannot be undone.</flux:subheading>
            </div>

            <form method="POST" action="{{ route('projects.destroy', $project) }}">
                @csrf
                @method('DELETE')

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" type="submit">Delete project</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    @if ($errors->has('title') || $errors->has('github_url'))
        <div
            x-data="{}"
            x-init="$dispatch('open-modal', 'edit-project-details')"
            class="hidden"
            data-test="open-edit-project-modal"
        ></div>
    @endif

    <flux:modal name="edit-project-details" :show="$errors->has('title') || $errors->has('github_url')" class="max-w-2xl" focusable>
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <flux:heading size="lg">Edit project details</flux:heading>
                <flux:subheading>Update the project title and repository link.</flux:subheading>
            </div>

            <flux:input
                name="title"
                :label="__('Project title')"
                :value="old('title', $project->title)"
                type="text"
                required
            />

            <flux:input
                name="github_url"
                :label="__('GitHub URL')"
                :value="old('github_url', $project->github_url)"
                type="url"
                required
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</x-layouts::app>
