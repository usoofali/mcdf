<?php

use App\Models\Member;
use App\Models\State;
use App\Models\Lga;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Members'])] class extends VoltComponent
{
    use WithPagination;
    use AuthorizesRequests;

    public string $search = '';
    public ?string $status = null;
    public ?int $stateId = null;
    public ?int $lgaId = null;
    public array $lgas = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Member::class);
    }

    public function updatedStateId($value): void
    {
        $this->lgas = $value ? Lga::where('state_id', $value)->pluck('name', 'id')->toArray() : [];
        $this->lgaId = null;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingLgaId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function members()
    {
        return Member::query()
            ->with(['state', 'lga'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('middle_name', 'like', '%' . $this->search . '%')
                        ->orWhere('registration_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->stateId, fn ($query) => $query->where('state_id', $this->stateId))
            ->when($this->lgaId, fn ($query) => $query->where('lga_id', $this->lgaId))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function states()
    {
        return State::orderBy('name')->pluck('name', 'id');
    }

    public function delete(Member $member): void
    {
        $this->authorize('delete', $member);
        $member->delete();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ __('Members') }}</h1>
                @can('create', App\Models\Member::class)
                    <flux:button href="{{ route('members.create') }}" variant="primary" wire:navigate>
                        {{ __('Add Member') }}
                    </flux:button>
                @endcan
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="mb-4 grid gap-4 md:grid-cols-4">
                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Name or Registration No') }}" />

                    <flux:select wire:model.live="status" :label="__('Status')">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                    </flux:select>

                    <flux:select wire:model.live="stateId" :label="__('State')">
                        <option value="">{{ __('All States') }}</option>
                        @foreach($this->states as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="lgaId" :label="__('LGA')" :disabled="empty($lgas)">
                        <option value="">{{ __('All LGAs') }}</option>
                        @foreach($lgas as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Registration No') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('State/LGA') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->members as $member)
                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                    <td class="px-4 py-3">{{ $member->registration_no }}</td>
                                    <td class="px-4 py-3">{{ $member->full_name }}</td>
                                    <td class="px-4 py-3">
                                        {{ $member->state?->name }}{{ $member->lga ? ' / ' . $member->lga->name : '' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                            {{ $member->status === 'inactive' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}
                                            {{ $member->status === 'suspended' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                        ">
                                            {{ ucfirst($member->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <flux:button href="{{ route('members.show', $member) }}" variant="ghost" size="sm" wire:navigate>
                                                {{ __('View') }}
                                            </flux:button>
                                            @can('update', $member)
                                                <flux:button href="{{ route('members.edit', $member) }}" variant="ghost" size="sm" wire:navigate>
                                                    {{ __('Edit') }}
                                                </flux:button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-neutral-500">
                                        {{ __('No members found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->members->links() }}
                </div>
            </div>
        </div>
</div>

