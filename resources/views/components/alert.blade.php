@props([
    'type' => 'success',
    'dismissible' => true,
])

@php
    $styles = [
        'success' => 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-200 dark:border-green-800',
        'error' => 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-200 dark:border-red-800',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-200 dark:border-yellow-800',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-200 dark:border-blue-800',
    ];

    $icons = [
        'success' => 'check-circle',
        'error' => 'x-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'information-circle',
    ];

    $style = $styles[$type] ?? $styles['success'];
    $icon = $icons[$type] ?? $icons['success'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 {$style}"]) }} role="alert">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <flux:icon :name="$icon" class="h-5 w-5" />
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm font-medium">{{ $slot }}</p>
        </div>
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button type="button" class="inline-flex rounded-md p-1.5 hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2" x-on:click="$el.closest('div').remove()">
                    <flux:icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>
        @endif
    </div>
</div>

