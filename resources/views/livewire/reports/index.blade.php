<?php

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Reports'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public function mount(): void
    {
        if (!auth()->user()->hasAnyRole(['admin', 'finance', 'health'])) {
            abort(403, 'Unauthorized');
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Reports') }}</h1>
                <p class="text-sm text-neutral-500">{{ __('View and export system reports') }}</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <a href="{{ route('reports.contributions') }}" wire:navigate class="group rounded-xl border border-neutral-200 bg-white p-6 transition hover:border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:border-neutral-600">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                        <flux:icon name="currency-dollar" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Contribution Summary') }}</h3>
                </div>
                <p class="text-sm text-neutral-500">{{ __('View contributions by period, plan, and location with totals') }}</p>
            </a>

            <a href="{{ route('reports.loans') }}" wire:navigate class="group rounded-xl border border-neutral-200 bg-white p-6 transition hover:border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:border-neutral-600">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900/20">
                        <flux:icon name="banknotes" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Loan Performance') }}</h3>
                </div>
                <p class="text-sm text-neutral-500">{{ __('View loan statistics, disbursed amounts, outstanding balances, and defaults') }}</p>
            </a>

            <a href="{{ route('reports.dependents') }}" wire:navigate class="group rounded-xl border border-neutral-200 bg-white p-6 transition hover:border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:border-neutral-600">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/20">
                        <flux:icon name="user-group" class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <h3 class="text-lg font-semibold">{{ __('Dependent Summary') }}</h3>
                </div>
                <p class="text-sm text-neutral-500">{{ __('View dependent statistics by age, relationship, and eligibility') }}</p>
            </a>
        </div>
    </div>
</div>

