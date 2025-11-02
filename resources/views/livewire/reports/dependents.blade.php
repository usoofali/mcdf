<?php

use App\Models\Dependent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Dependent Summary Report'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public function mount(): void
    {
        if (!auth()->user()->hasAnyRole(['admin', 'health', 'finance'])) {
            abort(403, 'Unauthorized');
        }
    }

    public function exportCsv(): void
    {
        // CSV export will be implemented
        session()->flash('info', __('CSV export feature coming soon.'));
    }

    #[Computed]
    public function summary()
    {
        $total = Dependent::count();

        $byRelationship = Dependent::select('relationship', DB::raw('COUNT(*) as count'))
            ->groupBy('relationship')
            ->get()
            ->keyBy('relationship');

        // Age groups for children
        $childrenByAge = Dependent::where('relationship', 'child')
            ->selectRaw('
                CASE
                    WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 <= 5 THEN "0-5 years"
                    WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 <= 10 THEN "6-10 years"
                    WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 <= 15 THEN "11-15 years"
                    ELSE "Over 15 years"
                END as age_group,
                COUNT(*) as count
            ')
            ->groupBy('age_group')
            ->get();

        // Eligible vs not eligible
        $eligible = Dependent::where('relationship', 'child')
            ->whereRaw('DATEDIFF(CURDATE(), date_of_birth) / 365.25 <= 15')
            ->count();

        $notEligible = Dependent::where('relationship', 'child')
            ->whereRaw('DATEDIFF(CURDATE(), date_of_birth) / 365.25 > 15')
            ->count();

        return [
            'total' => $total,
            'by_relationship' => [
                'spouse' => $byRelationship->get('spouse')->count ?? 0,
                'child' => $byRelationship->get('child')->count ?? 0,
                'other' => $byRelationship->get('other')->count ?? 0,
            ],
            'children_by_age' => $childrenByAge,
            'children_eligible' => $eligible,
            'children_not_eligible' => $notEligible,
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Dependent Summary Report') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('View dependent statistics by age and relationship') }}</p>
            </div>
            <flux:button wire:click="exportCsv" variant="primary" size="sm" class="w-full sm:w-auto">
                {{ __('Export CSV') }}
            </flux:button>
        </div>


        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Summary Cards -->
            <div class="grid gap-4 p-4 md:grid-cols-4">
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="text-sm text-neutral-500">{{ __('Total Dependents') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['total'] }}</div>
                </div>
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Spouses') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['by_relationship']['spouse'] }}</div>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Children') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['by_relationship']['child'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Other') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['by_relationship']['other'] }}</div>
                </div>
            </div>

            <!-- By Relationship -->
            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                <h3 class="mb-4 font-semibold">{{ __('By Relationship') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Relationship') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Count') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Percentage') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->summary['by_relationship'] as $relationship => $count)
                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                    <td class="px-4 py-3 text-sm">{{ ucfirst($relationship) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium">{{ $count }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        {{ $this->summary['total'] > 0 ? number_format(($count / $this->summary['total']) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Children by Age Group -->
            @if($this->summary['by_relationship']['child'] > 0)
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    <h3 class="mb-4 font-semibold">{{ __('Children by Age Group') }}</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-neutral-50 dark:bg-neutral-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Age Group') }}</th>
                                        <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Count') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->summary['children_by_age'] as $item)
                                        <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                            <td class="px-4 py-3 text-sm">{{ $item->age_group }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium">{{ $item->count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                            <h4 class="mb-3 font-semibold">{{ __('Children Eligibility') }}</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="font-medium">{{ __('Eligible (≤15 years):') }}</dt>
                                    <dd class="font-semibold text-green-600 dark:text-green-400">{{ $this->summary['children_eligible'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium">{{ __('Not Eligible (>15 years):') }}</dt>
                                    <dd class="font-semibold text-red-600 dark:text-red-400">{{ $this->summary['children_not_eligible'] }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

