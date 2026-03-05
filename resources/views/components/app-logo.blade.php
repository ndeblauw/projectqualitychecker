@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Project Quality Checker" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-sky-600 text-accent-foreground">
            <flux:icon.shield-check class="size-5 text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Project Quality Checker" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-sky-600 text-accent-foreground">
            <flux:icon.shield-check class="size-5 text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
