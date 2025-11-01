<?php

use App\Models\Contribution;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Contributions'])] class extends VoltComponent
{
    use WithPagination;
    use AuthorizesRequests;

    public string $search = '';
    public ?string $status = null;
    public ?int $memberId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Contribution::class);
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
    public function contributions()
    {
        return Contribution::query()
            ->with(['member', 'plan', 'recordedBy', 'reviewedBy'])
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
                <h1 class="text-2xl font-bold">{{ __('Contributions') }}</h1>
                @can('create', App\Models\Contribution::class)
                    <flux:button href="{{ route('contributions.create') }}" variant="primary" wire:navigate>
                        {{ __('Record Contribution') }}
                    </flux:button>
                @endcan
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="mb-4 grid gap-4 md:grid-cols-2">
                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Member name or registration no') }}" />

                    <flux:select wire:model.live="status" :label="__('Status')">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="submitted">{{ __('Submitted') }}</option>
                        <option value="pending_review">{{ __('Pending Review') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                        <option value="paid">{{ __('Paid') }}</option>
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
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $contribution->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                            {{ $contribution->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                            {{ $contribution->status === 'pending_review' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                            {{ $contribution->status === 'submitted' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}
                                            {{ $contribution->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $contribution->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:button href="{{ route('members.show', $contribution->member) }}" variant="ghost" size="sm" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-500">
                                        {{ __('No contributions found.') }}
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
</div>

