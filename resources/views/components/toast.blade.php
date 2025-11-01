@props([
    'type' => 'success',
    'timeout' => 5000,
])

@php
    $styles = [
        'success' => 'bg-green-500',
        'error' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500',
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

<div
    x-data="{
        show: false,
        init() {
            this.show = true;
            setTimeout(() => {
                this.show = false;
            }, {{ $timeout }});
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2"
    class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg shadow-lg ring-1 ring-black ring-opacity-5"
    {{ $attributes }}
>
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <flux:icon :name="$icon" class="h-5 w-5 text-white" />
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-white">{{ $slot }}</p>
            </div>
            <div class="ml-4 flex flex-shrink-0">
                <button
                    type="button"
                    class="inline-flex rounded-md text-white hover:text-white focus:outline-none focus:ring-2 focus:ring-white"
                    x-on:click="show = false"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>
    <div class="{{ $style }} h-1 w-full" x-show="show" x-transition:leave="transition ease-in duration-{{ $timeout }}"></div>
</div>

