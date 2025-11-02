<?php

use App\Models\Contribution;
use App\Services\ContributionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Review Contributions'])] class extends VoltComponent
{
    use WithPagination;
    use AuthorizesRequests;

    public ?string $status = null;

    public function mount(): void
    {
        $this->authorize('review', Contribution::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function contributions()
    {
        return Contribution::query()
            ->with(['member', 'plan', 'recordedBy'])
            ->whereIn('status', ['submitted', 'pending_review'])
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);
    }

    public function approve(Contribution $contribution, ContributionService $service): void
    {
        try {
            $service->review($contribution, Auth::user(), 'approve');
            session()->flash('status', __('Contribution approved successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(Contribution $contribution, ContributionService $service, string $reason): void
    {
        if (empty(trim($reason))) {
            session()->flash('error', __('Rejection reason is required.'));
            return;
        }

        try {
            $service->review($contribution, Auth::user(), 'reject', $reason);
            session()->flash('status', __('Contribution rejected.'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold sm:text-2xl">{{ __('Review Contributions') }}</h1>
                </div>
                <flux:button href="{{ route('contributions.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>


            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="mb-4">
                    <flux:select wire:model.live="status" :label="__('Filter by Status')">
                        <option value="">{{ __('All Pending') }}</option>
                        <option value="submitted">{{ __('Submitted') }}</option>
                        <option value="pending_review">{{ __('Pending Review') }}</option>
                    </flux:select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Plan') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->contributions as $contribution)
                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                    <td class="px-4 py-3 text-sm">{{ $contribution->payment_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $contribution->member->full_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $contribution->plan->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ number_format($contribution->amount, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ ucfirst(str_replace('_', ' ', $contribution->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 sm:px-4">
                                        <div class="flex items-center gap-2">
                                            <flux:button wire:click="$dispatch('open-modal', { component: 'contribution-detail', contribution: {{ $contribution->id }} })" variant="ghost" size="sm" class="w-full sm:w-auto">
                                                {{ __('Review') }}
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-500">
                                        {{ __('No contributions pending review.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->contributions->links() }}
                </div>
            </div>
        </div>
    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-alert variant="error" :timeout="5000">
            {{ session('error') }}
        </x-alert>
    </div>
    @endif
    @if (session()->has('success'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-alert variant="success" :timeout="5000">
            {{ session('success') }}
        </x-alert>
    </div>
    @endif
</div>

