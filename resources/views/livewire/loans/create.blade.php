<?php

use App\Models\Loan;
use App\Models\Member;
use App\Services\LoanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Create Loan'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public string $memberSearch = '';
    public ?int $memberId = null;
    public string $amount = '';
    public ?string $purpose = null;
    public ?string $remarks = null;

    public ?int $member = null;

    public function mount(?int $member = null): void
    {
        $this->authorize('create', Loan::class);
        $this->member = $member;
        if ($member) {
            $this->memberId = $member;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'memberId' => ['required', 'exists:members,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $member = Member::findOrFail($validated['memberId']);
        $loanService = app(LoanService::class);

        $loanService->apply($member, [
            'amount' => $validated['amount'],
            'purpose' => $validated['purpose'],
            'remarks' => $validated['remarks'],
        ]);

        $this->redirect(route('loans.index'), navigate: true);
    }

    public function updatedMemberSearch(): void
    {
        // This can be used for autocomplete if needed
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Create Loan') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('Create a new loan application') }}</p>
            </div>
            <flux:button href="{{ route('loans.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        @if(session()->has('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="p-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="memberSearch" :label="__('Search Member')" placeholder="{{ __('Search by name or registration number...') }}" />
                        @if($memberSearch)
                            <div class="mt-2 rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                                @php
                                    $members = Member::where('first_name', 'like', '%' . $memberSearch . '%')
                                        ->orWhere('last_name', 'like', '%' . $memberSearch . '%')
                                        ->orWhere('registration_no', 'like', '%' . $memberSearch . '%')
                                        ->limit(10)
                                        ->get();
                                @endphp
                                @foreach($members as $m)
                                    <button
                                        type="button"
                                        wire:click="$set('memberId', {{ $m->id }})"
                                        class="w-full px-4 py-2 text-left text-sm hover:bg-neutral-50 dark:hover:bg-neutral-700"
                                    >
                                        <div class="font-medium">{{ $m->full_name }}</div>
                                        <div class="text-xs text-neutral-500">{{ $m->registration_no }}</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @if($memberId && !$memberSearch)
                            @php
                                $selectedMemberObj = Member::find($memberId);
                            @endphp
                            @if($selectedMemberObj)
                                <div class="mt-2 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                                    <div class="text-sm font-medium text-green-800 dark:text-green-200">
                                        {{ __('Selected:') }} {{ $selectedMemberObj->full_name }} ({{ $selectedMemberObj->registration_no }})
                                    </div>
                                    <flux:button type="button" wire:click="$set('memberId', null)" variant="ghost" size="sm" class="mt-1">
                                        {{ __('Clear') }}
                                    </flux:button>
                                </div>
                            @endif
                        @endif
                        @error('memberId')
                            <flux:text class="mt-1 text-sm text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <flux:input wire:model="amount" type="number" step="0.01" :label="__('Loan Amount')" required />
                    <flux:textarea wire:model="purpose" :label="__('Purpose')" rows="3" />
                    <flux:textarea wire:model="remarks" :label="__('Remarks')" rows="3" />
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                        {{ __('Create Loan') }}
                    </flux:button>
                    <flux:button href="{{ route('loans.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>

