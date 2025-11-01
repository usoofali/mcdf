<?php

use App\Models\Dependent;
use App\Models\Member;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Member Details'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public Member $member;
    public string $activeTab = 'overview';
    public bool $showDependentModal = false;
    public ?int $editingDependentId = null;
    public string $dependentName = '';
    public ?string $dependentDob = null;
    public string $dependentRelationship = 'child';
    public ?string $dependentNin = null;

    public function mount(Member $member): void
    {
        $this->authorize('view', $member);
        $this->member = $member->load(['state', 'lga', 'dependents', 'loans.repayments', 'contributions.plan']);
    }

    #[Computed]
    public function dependents()
    {
        return $this->member->dependents()->latest()->get();
    }

    public function openDependentModal(?int $id = null): void
    {
        $this->showDependentModal = true;
        $this->editingDependentId = $id;

        if ($id) {
            $dependent = Dependent::findOrFail($id);
            $this->dependentName = $dependent->name;
            $this->dependentDob = $dependent->date_of_birth->format('Y-m-d');
            $this->dependentRelationship = $dependent->relationship;
            $this->dependentNin = $dependent->nin;
        } else {
            $this->resetDependentForm();
        }
    }

    public function closeDependentModal(): void
    {
        $this->showDependentModal = false;
        $this->editingDependentId = null;
        $this->resetDependentForm();
    }

    public function resetDependentForm(): void
    {
        $this->dependentName = '';
        $this->dependentDob = null;
        $this->dependentRelationship = 'child';
        $this->dependentNin = null;
    }

    public function saveDependent(): void
    {
        $validated = $this->validate([
            'dependentName' => ['required', 'string', 'max:255'],
            'dependentDob' => ['required', 'date'],
            'dependentRelationship' => ['required', 'in:spouse,child,other'],
            'dependentNin' => ['nullable', 'string', 'size:11', 'digits:11', 'unique:dependents,nin,' . $this->editingDependentId],
        ]);

        if ($this->editingDependentId) {
            $dependent = Dependent::findOrFail($this->editingDependentId);
            $dependent->update([
                'name' => $validated['dependentName'],
                'date_of_birth' => $validated['dependentDob'],
                'relationship' => $validated['dependentRelationship'],
                'nin' => $validated['dependentNin'],
            ]);
        } else {
            Dependent::create([
                'member_id' => $this->member->id,
                'name' => $validated['dependentName'],
                'date_of_birth' => $validated['dependentDob'],
                'relationship' => $validated['dependentRelationship'],
                'nin' => $validated['dependentNin'],
            ]);
        }

        $this->member->refresh();
        $this->closeDependentModal();
    }

    public function deleteDependent(int $id): void
    {
        $dependent = Dependent::findOrFail($id);
        $dependent->delete();
        $this->member->refresh();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $member->full_name }}</h1>
                    <p class="text-sm text-neutral-500">{{ $member->registration_no }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button href="{{ route('members.index') }}" variant="ghost" wire:navigate>
                        {{ __('Back') }}
                    </flux:button>
                    @can('update', $member)
                        <flux:button href="{{ route('members.edit', $member) }}" variant="primary" wire:navigate>
                            {{ __('Edit') }}
                        </flux:button>
                    @endcan
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                <div class="border-b border-neutral-200 dark:border-neutral-700">
                    <div class="flex gap-2 p-2">
                        <flux:button 
                            wire:click="$set('activeTab', 'overview')" 
                            variant="{{ $activeTab === 'overview' ? 'primary' : 'ghost' }}"
                            size="sm"
                        >
                            {{ __('Overview') }}
                        </flux:button>
                        <flux:button 
                            wire:click="$set('activeTab', 'dependents')" 
                            variant="{{ $activeTab === 'dependents' ? 'primary' : 'ghost' }}"
                            size="sm"
                        >
                            {{ __('Dependents') }}
                        </flux:button>
                        <flux:button 
                            wire:click="$set('activeTab', 'contributions')" 
                            variant="{{ $activeTab === 'contributions' ? 'primary' : 'ghost' }}"
                            size="sm"
                        >
                            {{ __('Contributions') }}
                        </flux:button>
                        <flux:button 
                            wire:click="$set('activeTab', 'loans')" 
                            variant="{{ $activeTab === 'loans' ? 'primary' : 'ghost' }}"
                            size="sm"
                        >
                            {{ __('Loans') }}
                        </flux:button>
                        <flux:button 
                            wire:click="$set('activeTab', 'activity')" 
                            variant="{{ $activeTab === 'activity' ? 'primary' : 'ghost' }}"
                            size="sm"
                        >
                            {{ __('Activity') }}
                        </flux:button>
                    </div>
                </div>

                <div class="p-6">
                    @if($activeTab === 'overview')
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <h3 class="mb-2 font-semibold">{{ __('Personal Information') }}</h3>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="font-medium">{{ __('Registration No') }}</dt>
                                        <dd>{{ $member->registration_no }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Full Name') }}</dt>
                                        <dd>{{ $member->full_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Date of Birth') }}</dt>
                                        <dd>{{ $member->date_of_birth?->format('F j, Y') ?? __('N/A') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Gender') }}</dt>
                                        <dd>{{ ucfirst($member->gender ?? __('N/A')) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('NIN') }}</dt>
                                        <dd>{{ $member->nin ?? __('N/A') }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <h3 class="mb-2 font-semibold">{{ __('Contact Information') }}</h3>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="font-medium">{{ __('Email') }}</dt>
                                        <dd>{{ $member->email ?? __('N/A') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Phone') }}</dt>
                                        <dd>{{ $member->phone ?? __('N/A') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Address') }}</dt>
                                        <dd>{{ $member->address ?? __('N/A') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('State/LGA') }}</dt>
                                        <dd>{{ $member->state?->name }}{{ $member->lga ? ' / ' . $member->lga->name : '' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <h3 class="mb-2 font-semibold">{{ __('Membership Information') }}</h3>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="font-medium">{{ __('Status') }}</dt>
                                        <dd>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                {{ $member->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                {{ $member->status === 'inactive' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}
                                                {{ $member->status === 'suspended' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                            ">
                                                {{ ucfirst($member->status) }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Registration Date') }}</dt>
                                        <dd>{{ $member->registration_date->format('F j, Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium">{{ __('Eligibility Start Date') }}</dt>
                                        <dd>{{ $member->eligibility_start_date?->format('F j, Y') ?? __('N/A') }}</dd>
                                    </div>
                                </dl>
                            </div>
                            @if($member->notes)
                                <div class="md:col-span-2">
                                    <h3 class="mb-2 font-semibold">{{ __('Notes') }}</h3>
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $member->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @elseif($activeTab === 'dependents')
                        <div class="space-y-4">
                            @if(session()->has('success'))
                                <x-alert type="success">{{ session('success') }}</x-alert>
                            @endif

                            @if(session()->has('error'))
                                <x-alert type="error">{{ session('error') }}</x-alert>
                            @endif

                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">{{ __('Dependents') }}</h3>
                                <flux:button wire:click="openDependentModal()" variant="primary" size="sm">
                                    {{ __('Add Dependent') }}
                                </flux:button>
                            </div>

                            @if($this->dependents->count() > 0)
                                <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                                    <table class="w-full">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Name') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date of Birth') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Age') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Relationship') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('NIN') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Eligible') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($this->dependents as $dependent)
                                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                                    <td class="px-4 py-3 text-sm">{{ $dependent->name }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ $dependent->date_of_birth->format('M j, Y') }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ $dependent->age }} {{ __('years') }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ ucfirst($dependent->relationship) }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ $dependent->nin ?? __('N/A') }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $dependent->isEligible() ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                                            {{ $dependent->isEligible() ? __('Yes') : __('No') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <flux:button wire:click="openDependentModal({{ $dependent->id }})" variant="ghost" size="sm">
                                                                {{ __('Edit') }}
                                                            </flux:button>
                                                            <flux:button wire:click="deleteDependent({{ $dependent->id }})" wire:confirm="{{ __('Are you sure you want to delete this dependent?') }}" variant="ghost" size="sm">
                                                                {{ __('Delete') }}
                                                            </flux:button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                                    <p class="text-sm text-neutral-500">{{ __('No dependents added yet.') }}</p>
                                </div>
                            @endif
                        </div>

                        <flux:modal name="dependent-modal" :show="$showDependentModal" focusable class="max-w-lg">
                            <form wire:submit="saveDependent" class="space-y-4">
                                <div>
                                    <flux:heading size="lg">{{ $editingDependentId ? __('Edit Dependent') : __('Add Dependent') }}</flux:heading>
                                    <flux:subheading>{{ __('Add or update dependent information for this member.') }}</flux:subheading>
                                </div>

                                <flux:input wire:model="dependentName" :label="__('Name')" required />
                                <flux:input wire:model="dependentDob" type="date" :label="__('Date of Birth')" required />
                                <flux:select wire:model="dependentRelationship" :label="__('Relationship')" required>
                                    <option value="spouse">{{ __('Spouse') }}</option>
                                    <option value="child">{{ __('Child') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </flux:select>
                                <flux:input wire:model="dependentNin" :label="__('NIN (11 digits)')" maxlength="11" placeholder="{{ __('Optional - 11-digit National ID') }}" />
                                <flux:text class="text-xs text-neutral-500">{{ __('If provided, NIN must be exactly 11 digits and unique.') }}</flux:text>

                                <div class="flex items-center gap-2 justify-end">
                                    <flux:modal.close>
                                        <flux:button type="button" wire:click="closeDependentModal" variant="ghost">
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="primary">
                                        {{ $editingDependentId ? __('Update') : __('Add') }}
                                    </flux:button>
                                </div>
                            </form>
                        </flux:modal>
                    @elseif($activeTab === 'contributions')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">{{ __('Contributions') }}</h3>
                                @can('submit', \App\Models\Contribution::class)
                                    <flux:button href="{{ route('contributions.submit') }}" variant="primary" size="sm" wire:navigate>
                                        {{ __('Submit Contribution') }}
                                    </flux:button>
                                @endcan
                            </div>

                            @if($member->contributions->count() > 0)
                                <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                                    <table class="w-full">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Plan') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Amount') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Method') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($member->contributions as $contribution)
                                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                                    <td class="px-4 py-3 text-sm">{{ $contribution->payment_date->format('M j, Y') }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ $contribution->plan->name }}</td>
                                                    <td class="px-4 py-3 text-sm font-medium">{{ number_format($contribution->amount, 2) }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ ucfirst($contribution->payment_method) }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                            {{ $contribution->status === 'pending_review' || $contribution->status === 'submitted' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                                            {{ $contribution->status === 'approved' || $contribution->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                            {{ $contribution->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                                        ">
                                                            {{ ucfirst(str_replace('_', ' ', $contribution->status)) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-semibold">
                                                <td class="px-4 py-3 text-right" colspan="2">{{ __('Total:') }}</td>
                                                <td class="px-4 py-3 text-sm">{{ number_format($member->contributions->sum('amount'), 2) }}</td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                                    <p class="text-sm text-neutral-500">{{ __('No contributions recorded for this member.') }}</p>
                                </div>
                            @endif
                        </div>
                    @elseif($activeTab === 'loans')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">{{ __('Loans') }}</h3>
                                @can('create', \App\Models\Loan::class)
                                    <flux:button href="{{ route('loans.create') }}?member={{ $member->id }}" variant="primary" size="sm" wire:navigate>
                                        {{ __('New Loan') }}
                                    </flux:button>
                                @endcan
                            </div>

                            @if($member->loans->count() > 0)
                                <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                                    <table class="w-full">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Amount') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Approved') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Balance') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Status') }}</th>
                                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($member->loans as $loan)
                                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                                    <td class="px-4 py-3 text-sm">{{ $loan->created_at->format('M j, Y') }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ number_format($loan->amount, 2) }}</td>
                                                    <td class="px-4 py-3 text-sm">{{ $loan->approved_amount ? number_format($loan->approved_amount, 2) : __('N/A') }}</td>
                                                    <td class="px-4 py-3 text-sm font-medium">{{ number_format($loan->balance, 2) }}</td>
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
                                                        <flux:button href="{{ route('loans.show', $loan) }}" variant="ghost" size="sm" wire:navigate>
                                                            {{ __('View') }}
                                                        </flux:button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                                    <p class="text-sm text-neutral-500">{{ __('No loans recorded for this member.') }}</p>
                                </div>
                            @endif
                        </div>
                    @elseif($activeTab === 'activity')
                        <div class="text-center text-sm text-neutral-500">
                            {{ __('Activity feature coming soon.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
</div>

