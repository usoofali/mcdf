<?php

use App\Models\Contribution;
use App\Models\ContributionPlan;
use App\Models\Lga;
use App\Models\State;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Contribution Summary Report'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $planId = null;
    public ?int $stateId = null;
    public ?int $lgaId = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAnyRole(['admin', 'finance'])) {
            abort(403, 'Unauthorized');
        }

        // Default to current year
        $this->startDate = now()->startOfYear()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function exportCsv(): void
    {
        // CSV export will be implemented
        session()->flash('info', __('CSV export feature coming soon.'));
    }

    #[Computed]
    public function summary()
    {
        $query = Contribution::query()
            ->join('members', 'contributions.member_id', '=', 'members.id')
            ->join('contribution_plans', 'contributions.contribution_plan_id', '=', 'contribution_plans.id')
            ->leftJoin('states', 'members.state_id', '=', 'states.id')
            ->leftJoin('lgas', 'members.lga_id', '=', 'lgas.id')
            ->whereIn('contributions.status', ['approved', 'paid'])
            ->when($this->startDate, fn ($q) => $q->where('contributions.payment_date', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->where('contributions.payment_date', '<=', $this->endDate))
            ->when($this->planId, fn ($q) => $q->where('contributions.contribution_plan_id', $this->planId))
            ->when($this->stateId, fn ($q) => $q->where('members.state_id', $this->stateId))
            ->when($this->lgaId, fn ($q) => $q->where('members.lga_id', $this->lgaId));

        $byPlan = (clone $query)
            ->select('contribution_plans.name as plan_name', DB::raw('SUM(contributions.amount) as total'))
            ->groupBy('contribution_plans.id', 'contribution_plans.name')
            ->get();

        $byState = (clone $query)
            ->select('states.name as state_name', DB::raw('SUM(contributions.amount) as total'))
            ->groupBy('states.id', 'states.name')
            ->get();

        $byLga = (clone $query)
            ->select('lgas.name as lga_name', 'states.name as state_name', DB::raw('SUM(contributions.amount) as total'))
            ->groupBy('lgas.id', 'lgas.name', 'states.name')
            ->get();

        $grandTotal = (clone $query)->sum('contributions.amount');

        return [
            'by_plan' => $byPlan,
            'by_state' => $byState,
            'by_lga' => $byLga,
            'grand_total' => $grandTotal,
            'count' => (clone $query)->count('contributions.id'),
        ];
    }

    #[Computed]
    public function plans()
    {
        return ContributionPlan::where('is_active', true)->orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function states()
    {
        return State::orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function lgas()
    {
        return $this->stateId ? Lga::where('state_id', $this->stateId)->orderBy('name')->pluck('name', 'id') : [];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Contribution Summary Report') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('View contribution summaries by period, plan, and location') }}</p>
            </div>
            <flux:button wire:click="exportCsv" variant="primary" size="sm" class="w-full sm:w-auto">
                {{ __('Export CSV') }}
            </flux:button>
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        @if(session()->has('info'))
            <x-alert type="info">{{ session('info') }}</x-alert>
        @endif

        <!-- Filters -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                <h3 class="mb-4 font-semibold">{{ __('Filters') }}</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model.live="startDate" type="date" :label="__('Start Date')" />
                    <flux:input wire:model.live="endDate" type="date" :label="__('End Date')" />
                    <flux:select wire:model.live="planId" :label="__('Contribution Plan')">
                        <option value="">{{ __('All Plans') }}</option>
                        @foreach($this->plans as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="stateId" :label="__('State')">
                        <option value="">{{ __('All States') }}</option>
                        @foreach($this->states as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="lgaId" :label="__('LGA')">
                        <option value="">{{ __('All LGAs') }}</option>
                        @foreach($this->lgas as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 p-4 md:grid-cols-2">
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="text-sm text-neutral-500">{{ __('Total Contributions') }}</div>
                    <div class="text-2xl font-bold">{{ number_format($this->summary['grand_total'], 2) }}</div>
                    <div class="text-xs text-neutral-500">{{ $this->summary['count'] }} {{ __('contributions') }}</div>
                </div>
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="text-sm text-neutral-500">{{ __('Period') }}</div>
                    <div class="text-lg font-semibold">
                        {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('M j, Y') : __('All') }}
                        {{ __('to') }}
                        {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('M j, Y') : __('All') }}
                    </div>
                </div>
            </div>

            <!-- By Plan -->
            @if($this->summary['by_plan']->count() > 0)
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    <h3 class="mb-4 font-semibold">{{ __('By Contribution Plan') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-neutral-50 dark:bg-neutral-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Plan') }}</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->summary['by_plan'] as $item)
                                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                        <td class="px-4 py-3 text-sm">{{ $item->plan_name }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium">{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- By State -->
            @if($this->summary['by_state']->count() > 0)
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    <h3 class="mb-4 font-semibold">{{ __('By State') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-neutral-50 dark:bg-neutral-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('State') }}</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->summary['by_state'] as $item)
                                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                        <td class="px-4 py-3 text-sm">{{ $item->state_name ?? __('N/A') }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium">{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- By LGA -->
            @if($this->summary['by_lga']->count() > 0)
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    <h3 class="mb-4 font-semibold">{{ __('By LGA') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-neutral-50 dark:bg-neutral-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('State') }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('LGA') }}</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->summary['by_lga'] as $item)
                                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                        <td class="px-4 py-3 text-sm">{{ $item->state_name ?? __('N/A') }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $item->lga_name ?? __('N/A') }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium">{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

