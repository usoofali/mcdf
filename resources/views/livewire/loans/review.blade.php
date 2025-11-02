<?php

use App\Models\Loan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Review Loans'])] class extends VoltComponent
{
    use WithPagination;
    use AuthorizesRequests;

    public ?string $status = null;

    public function mount(): void
    {
        $this->authorize('approve', Loan::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function loans()
    {
        return Loan::query()
            ->with(['member', 'approvedBy'])
            ->whereIn('status', ['pending', 'approved'])
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Review Loans') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('Approve or reject loan applications') }}</p>
            </div>
            <flux:button href="{{ route('loans.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>


        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                <flux:select wire:model.live="status" :label="__('Filter by Status')">
                    <option value="">{{ __('All Pending') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                </flux:select>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Member') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Purpose') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->loans as $loan)
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3 text-sm">{{ $loan->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium">{{ $loan->member->full_name }}</div>
                                    <div class="text-xs text-neutral-500">{{ $loan->member->registration_no }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">{{ number_format($loan->amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm">{{ Str::limit($loan->purpose ?? __('N/A'), 50) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                        {{ $loan->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
                                    </span>
                                </td>
                                <td class="px-2 py-3 sm:px-4">
                                    <div class="flex items-center gap-2">
                                        <flux:button href="{{ route('loans.show', $loan) }}" variant="ghost" size="sm" class="w-full sm:w-auto" wire:navigate>
                                            {{ __('Review') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-500">
                                    {{ __('No loans pending review.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                {{ $this->loans->links() }}
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

