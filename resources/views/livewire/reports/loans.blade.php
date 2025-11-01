<?php

use App\Models\Loan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Loan Performance Report'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public ?string $startDate = null;
    public ?string $endDate = null;

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
        $query = Loan::query()
            ->when($this->startDate, fn ($q) => $q->where('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->where('created_at', '<=', $this->endDate . ' 23:59:59'));

        $totalLoans = (clone $query)->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $disbursed = (clone $query)->where('status', 'disbursed')->count();
        $repaid = (clone $query)->where('status', 'repaid')->count();
        $defaulted = (clone $query)->where('status', 'defaulted')->count();

        $totalDisbursed = (clone $query)
            ->whereIn('status', ['disbursed', 'repaid', 'defaulted'])
            ->sum(DB::raw('COALESCE(approved_amount, amount)'));

        $totalOutstanding = (clone $query)
            ->whereIn('status', ['disbursed', 'defaulted'])
            ->get()
            ->sum(fn ($loan) => $loan->balance);

        $totalRepaid = Loan::query()
            ->join('loan_repayments', 'loans.id', '=', 'loan_repayments.loan_id')
            ->when($this->startDate, fn ($q) => $q->where('loan_repayments.payment_date', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->where('loan_repayments.payment_date', '<=', $this->endDate))
            ->sum('loan_repayments.amount');

        return [
            'total_loans' => $totalLoans,
            'pending' => $pending,
            'approved' => $approved,
            'disbursed' => $disbursed,
            'repaid' => $repaid,
            'defaulted' => $defaulted,
            'total_disbursed' => $totalDisbursed,
            'total_outstanding' => $totalOutstanding,
            'total_repaid' => $totalRepaid,
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Loan Performance Report') }}</h1>
                <p class="text-sm text-neutral-500">{{ __('View loan statistics and performance metrics') }}</p>
            </div>
            <flux:button wire:click="exportCsv" variant="primary" size="sm">
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
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model.live="startDate" type="date" :label="__('Start Date')" />
                    <flux:input wire:model.live="endDate" type="date" :label="__('End Date')" />
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 p-4 md:grid-cols-3 lg:grid-cols-5">
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="text-sm text-neutral-500">{{ __('Total Loans') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['total_loans'] }}</div>
                </div>
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Pending') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['pending'] }}</div>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Approved') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['approved'] }}</div>
                </div>
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Disbursed') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['disbursed'] }}</div>
                </div>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <div class="text-sm text-neutral-500">{{ __('Repaid') }}</div>
                    <div class="text-2xl font-bold">{{ $this->summary['repaid'] }}</div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                <h3 class="mb-4 font-semibold">{{ __('Financial Summary') }}</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                        <div class="text-sm text-neutral-500">{{ __('Total Disbursed') }}</div>
                        <div class="text-2xl font-bold">{{ number_format($this->summary['total_disbursed'], 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <div class="text-sm text-neutral-500">{{ __('Total Outstanding') }}</div>
                        <div class="text-2xl font-bold">{{ number_format($this->summary['total_outstanding'], 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                        <div class="text-sm text-neutral-500">{{ __('Total Repaid') }}</div>
                        <div class="text-2xl font-bold">{{ number_format($this->summary['total_repaid'], 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Defaulted Loans -->
            @if($this->summary['defaulted'] > 0)
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    <h3 class="mb-4 font-semibold text-red-600 dark:text-red-400">{{ __('Defaulted Loans') }}</h3>
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <div class="text-2xl font-bold text-red-800 dark:text-red-200">{{ $this->summary['defaulted'] }}</div>
                        <div class="text-sm text-red-700 dark:text-red-300">{{ __('loans in default status') }}</div>
                    </div>
                </div>
            @endif

            <!-- Period Info -->
            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-sm text-neutral-500">{{ __('Report Period:') }}</div>
                <div class="text-lg font-semibold">
                    {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('M j, Y') : __('All') }}
                    {{ __('to') }}
                    {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('M j, Y') : __('All') }}
                </div>
            </div>
        </div>
    </div>
</div>

