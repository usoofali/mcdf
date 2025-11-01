<?php

use App\Models\Member;
use App\Services\EligibilityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Eligibility Check'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public string $search = '';
    public ?int $memberId = null;
    public ?Member $selectedMember = null;
    public ?array $eligibility = null;

    public function mount(): void
    {
        // Allow access to health officers and admins
        if (!auth()->user()->hasAnyRole(['admin', 'health'])) {
            abort(403, 'Unauthorized');
        }
    }

    public function updatedSearch(): void
    {
        $this->memberId = null;
        $this->selectedMember = null;
        $this->eligibility = null;
    }

    public function selectMember(int $id): void
    {
        $this->memberId = $id;
        $member = Member::with(['dependents'])->findOrFail($id);
        $this->selectedMember = $member;

        $eligibilityService = app(EligibilityService::class);
        $this->eligibility = $eligibilityService->checkMemberEligibility($member);
    }

    public function clearSelection(): void
    {
        $this->memberId = null;
        $this->selectedMember = null;
        $this->eligibility = null;
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div>
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Health Eligibility Check') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('Check member eligibility for health benefits') }}</p>
            </div>
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        @if(session()->has('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live.debounce.300ms="search" :label="__('Search Member')" placeholder="{{ __('Search by name or registration number...') }}" />
                @if($search)
                    <div class="mt-2 rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                        @php
                            $members = Member::where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('registration_no', 'like', '%' . $search . '%')
                                ->limit(10)
                                ->get();
                        @endphp
                        @foreach($members as $member)
                            <button
                                type="button"
                                wire:click="selectMember({{ $member->id }})"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-neutral-50 dark:hover:bg-neutral-700"
                            >
                                <div class="font-medium">{{ $member->full_name }}</div>
                                <div class="text-xs text-neutral-500">{{ $member->registration_no }}</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($selectedMember && $eligibility)
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">{{ $selectedMember->full_name }}</h2>
                        <flux:button wire:click="clearSelection" variant="ghost" size="sm">
                            {{ __('Clear') }}
                        </flux:button>
                    </div>

                    <!-- Eligibility Status -->
                    <div class="mb-6 rounded-lg border p-4 {{ $eligibility['eligible'] ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }}">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                @if($eligibility['eligible'])
                                    <flux:icon name="check-circle" class="h-6 w-6 text-green-600 dark:text-green-400" />
                                @else
                                    <flux:icon name="x-circle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-2 font-semibold {{ $eligibility['eligible'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ $eligibility['eligible'] ? __('Eligible for Health Benefits') : __('Not Eligible for Health Benefits') }}
                                </h3>
                                @if(count($eligibility['reasons']) > 0)
                                    <ul class="list-disc space-y-1 pl-5 text-sm {{ $eligibility['eligible'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        @foreach($eligibility['reasons'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility Details -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                            <h4 class="mb-3 font-semibold">{{ __('Eligibility Criteria') }}</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="font-medium">{{ __('Days Since Registration:') }}</dt>
                                    <dd>{{ $eligibility['days_since_registration'] }} {{ __('days') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium">{{ __('Required Wait Period:') }}</dt>
                                    <dd>{{ $eligibility['wait_days'] }} {{ __('days') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium">{{ __('Status:') }}</dt>
                                    <dd>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $eligibility['member_status'] === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                            {{ $eligibility['member_status'] === 'inactive' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}
                                            {{ $eligibility['member_status'] === 'suspended' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                        ">
                                            {{ ucfirst($eligibility['member_status']) }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium">{{ __('Has Recent Contributions:') }}</dt>
                                    <dd>
                                        @if($eligibility['has_recent_contributions'])
                                            <flux:icon name="check-circle" class="h-5 w-5 text-green-600 dark:text-green-400" />
                                        @else
                                            <flux:icon name="x-circle" class="h-5 w-5 text-red-600 dark:text-red-400" />
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                            <h4 class="mb-3 font-semibold">{{ __('Member Information') }}</h4>
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="font-medium">{{ __('Registration No:') }}</dt>
                                    <dd>{{ $selectedMember->registration_no }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium">{{ __('Registration Date:') }}</dt>
                                    <dd>{{ $selectedMember->registration_date->format('F j, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium">{{ __('Eligibility Start Date:') }}</dt>
                                    <dd>{{ $selectedMember->eligibility_start_date?->format('F j, Y') ?? __('N/A') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Dependents Eligibility -->
                    @if($selectedMember->dependents->count() > 0)
                        <div class="mt-6 rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                                <h3 class="font-semibold">{{ __('Dependents Eligibility') }}</h3>
                            </div>
                            <div class="overflow-x-auto p-4">
                                <table class="w-full">
                                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Name') }}</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Relationship') }}</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Age') }}</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Eligible') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedMember->dependents as $dependent)
                                            @php
                                                $eligibilityService = app(EligibilityService::class);
                                                $dependentEligibility = $eligibilityService->checkDependentEligibility($dependent);
                                            @endphp
                                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                                <td class="px-4 py-3 text-sm">{{ $dependent->name }}</td>
                                                <td class="px-4 py-3 text-sm">{{ ucfirst($dependent->relationship) }}</td>
                                                <td class="px-4 py-3 text-sm">{{ $dependent->age }} {{ __('years') }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $dependentEligibility['eligible'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                        {{ $dependentEligibility['eligible'] ? __('Yes') : __('No') }}
                                                    </span>
                                                    @if(count($dependentEligibility['reasons']) > 0)
                                                        <div class="mt-1 text-xs text-neutral-500">
                                                            @foreach($dependentEligibility['reasons'] as $reason)
                                                                <div>{{ $reason }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-8 text-center text-neutral-500">
                    <flux:icon name="user" class="mx-auto mb-4 h-12 w-12 text-neutral-400" />
                    <p>{{ __('Search for a member to check eligibility') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

