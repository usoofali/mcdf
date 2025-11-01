<?php

use App\Models\Loan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Loans'])] class extends VoltComponent
{
    use WithPagination;
    use AuthorizesRequests;

    public string $search = '';
    public ?string $status = null;
    public ?int $memberId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Loan::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function loans()
    {
        return Loan::query()
            ->with(['member', 'approvedBy', 'disbursedBy'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('registration_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->memberId, fn ($query) => $query->where('member_id', $this->memberId))
            ->latest()
            ->paginate(15);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Loans') }}</h1>
                <p class="text-sm text-neutral-500">{{ __('Manage loan applications and repayments') }}</p>
            </div>
            @can('create', Loan::class)
                <flux:button href="{{ route('loans.create') }}" variant="primary" wire:navigate>
                    {{ __('New Loan') }}
                </flux:button>
            @endcan
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        @if(session()->has('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by member name or registration number...') }}" />
                    <flux:select wire:model.live="status" :label="__('Status')">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="disbursed">{{ __('Disbursed') }}</option>
                        <option value="repaid">{{ __('Repaid') }}</option>
                        <option value="defaulted">{{ __('Defaulted') }}</option>
                    </flux:select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Member') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Approved') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Balance') }}</th>
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
                                <td class="px-4 py-3 text-sm">{{ number_format($loan->amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm">{{ $loan->approved_amount ? number_format($loan->approved_amount, 2) : __('N/A') }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($loan->balance, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                        {{ $loan->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                        {{ $loan->status === 'disbursed' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                        {{ $loan->status === 'repaid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                        {{ $loan->status === 'defaulted' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <flux:button href="{{ route('loans.show', $loan) }}" variant="ghost" size="sm" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-neutral-500">
                                    {{ __('No loans found.') }}
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
</div>

