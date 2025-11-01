<?php

use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Loan Details'])] class extends VoltComponent
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Loan $loan;
    public bool $showApproveModal = false;
    public bool $showDisburseModal = false;
    public bool $showRepaymentModal = false;
    public bool $showDefaultModal = false;

    // Approve fields
    public string $approvedAmount = '';
    public string $interestRate = '0';
    public ?int $repaymentPeriodMonths = null;
    public ?string $dueDate = null;
    public ?string $approveRemarks = null;

    // Disburse fields
    public ?string $disburseDate = null;
    public ?string $disburseRemarks = null;

    // Repayment fields
    public string $repaymentAmount = '';
    public ?string $repaymentDate = null;
    public string $repaymentMethod = 'cash';
    public ?string $repaymentRef = null;
    public ?string $repaymentNotes = null;
    public ?TemporaryUploadedFile $repaymentReceipt = null;

    // Default fields
    public ?string $defaultReason = null;

    public function mount(Loan $loan): void
    {
        $this->authorize('view', $loan);
        $this->loan = $loan->load(['member', 'approvedBy', 'disbursedBy', 'repayments.createdBy']);
        $this->approvedAmount = (string) ($loan->approved_amount ?? $loan->amount);
    }

    #[Computed]
    public function repayments()
    {
        return $this->loan->repayments()->latest()->get();
    }

    public function openApproveModal(): void
    {
        $this->authorize('approve', Loan::class);
        $this->showApproveModal = true;
        $this->approvedAmount = (string) ($this->loan->approved_amount ?? $this->loan->amount);
        $this->interestRate = (string) ($this->loan->interest_rate ?? 0);
        $this->repaymentPeriodMonths = $this->loan->repayment_period_months;
        $this->dueDate = $this->loan->due_date?->format('Y-m-d');
    }

    public function closeApproveModal(): void
    {
        $this->showApproveModal = false;
        $this->resetApproveFields();
    }

    public function resetApproveFields(): void
    {
        $this->approvedAmount = (string) ($this->loan->approved_amount ?? $this->loan->amount);
        $this->interestRate = '0';
        $this->repaymentPeriodMonths = null;
        $this->dueDate = null;
        $this->approveRemarks = null;
    }

    public function approve(): void
    {
        $this->authorize('approve', Loan::class);

        $validated = $this->validate([
            'approvedAmount' => ['required', 'numeric', 'min:1'],
            'interestRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'repaymentPeriodMonths' => ['nullable', 'integer', 'min:1'],
            'dueDate' => ['nullable', 'date'],
            'approveRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $loanService = app(LoanService::class);
        $loanService->approve($this->loan, Auth::user(), [
            'approved_amount' => $validated['approvedAmount'],
            'interest_rate' => $validated['interestRate'] ?? 0,
            'repayment_period_months' => $validated['repaymentPeriodMonths'],
            'due_date' => $validated['dueDate'],
            'remarks' => $validated['approveRemarks'],
        ]);

        $this->loan->refresh();
        $this->closeApproveModal();
    }

    public function openDisburseModal(): void
    {
        $this->authorize('disburse', Loan::class);
        $this->showDisburseModal = true;
        $this->disburseDate = now()->format('Y-m-d');
    }

    public function closeDisburseModal(): void
    {
        $this->showDisburseModal = false;
        $this->disburseDate = null;
        $this->disburseRemarks = null;
    }

    public function disburse(): void
    {
        $this->authorize('disburse', Loan::class);

        $validated = $this->validate([
            'disburseDate' => ['required', 'date'],
            'disburseRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $loanService = app(LoanService::class);
            $loanService->disburse($this->loan, Auth::user(), [
                'disbursed_date' => $validated['disburseDate'],
            ]);

            $this->loan->refresh();
            $this->closeDisburseModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRepaymentModal(): void
    {
        $this->authorize('recordRepayment', Loan::class);
        $this->showRepaymentModal = true;
        $this->repaymentAmount = (string) $this->loan->balance;
        $this->repaymentDate = now()->format('Y-m-d');
    }

    public function closeRepaymentModal(): void
    {
        $this->showRepaymentModal = false;
        $this->resetRepaymentFields();
    }

    public function resetRepaymentFields(): void
    {
        $this->repaymentAmount = '';
        $this->repaymentDate = null;
        $this->repaymentMethod = 'cash';
        $this->repaymentRef = null;
        $this->repaymentNotes = null;
        $this->repaymentReceipt = null;
    }

    public function recordRepayment(): void
    {
        $this->authorize('recordRepayment', Loan::class);

        $validated = $this->validate([
            'repaymentAmount' => ['required', 'numeric', 'min:0.01', 'max:' . $this->loan->balance],
            'repaymentDate' => ['required', 'date'],
            'repaymentMethod' => ['required', 'in:cash,transfer'],
            'repaymentRef' => ['nullable', 'string', 'max:100', 'unique:loan_repayments,payment_ref'],
            'repaymentNotes' => ['nullable', 'string', 'max:1000'],
            'repaymentReceipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $loanService = app(LoanService::class);
            $loanService->recordRepayment(
                $this->loan,
                Auth::user(),
                [
                    'amount' => $validated['repaymentAmount'],
                    'payment_date' => $validated['repaymentDate'],
                    'payment_method' => $validated['repaymentMethod'],
                    'payment_ref' => $validated['repaymentRef'] ?? null,
                    'notes' => $validated['repaymentNotes'] ?? null,
                ],
                $this->repaymentReceipt
            );

            $this->loan->refresh();
            $this->closeRepaymentModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openDefaultModal(): void
    {
        $this->authorize('update', $this->loan);
        $this->showDefaultModal = true;
    }

    public function closeDefaultModal(): void
    {
        $this->showDefaultModal = false;
        $this->defaultReason = null;
    }

    public function markAsDefaulted(): void
    {
        $this->authorize('update', $this->loan);

        $validated = $this->validate([
            'defaultReason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $loanService = app(LoanService::class);
            $loanService->markAsDefaulted($this->loan, Auth::user(), $validated['defaultReason']);

            $this->loan->refresh();
            $this->closeDefaultModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Loan Details') }}</h1>
                <p class="text-sm text-neutral-500">{{ __('View and manage loan information') }}</p>
            </div>
            <flux:button href="{{ route('loans.index') }}" variant="ghost" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        @if(session()->has('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            @can('approve', Loan::class)
                @if($loan->status === 'pending')
                    <flux:button wire:click="openApproveModal" variant="primary" size="sm">
                        {{ __('Approve Loan') }}
                    </flux:button>
                @endif
            @endcan

            @can('disburse', Loan::class)
                @if($loan->status === 'approved')
                    <flux:button wire:click="openDisburseModal" variant="primary" size="sm">
                        {{ __('Disburse Loan') }}
                    </flux:button>
                @endif
            @endcan

            @can('recordRepayment', Loan::class)
                @if($loan->isDisbursed() && $loan->balance > 0)
                    <flux:button wire:click="openRepaymentModal" variant="primary" size="sm">
                        {{ __('Record Repayment') }}
                    </flux:button>
                @endif
            @endcan

            @can('update', $loan)
                @if($loan->isDisbursed() && $loan->status !== 'repaid' && $loan->status !== 'defaulted')
                    <flux:button wire:click="openDefaultModal" variant="danger" size="sm">
                        {{ __('Mark as Defaulted') }}
                    </flux:button>
                @endif
            @endcan
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <h3 class="mb-4 font-semibold">{{ __('Loan Information') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="font-medium">{{ __('Member') }}</dt>
                        <dd>
                            <a href="{{ route('members.show', $loan->member) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                                {{ $loan->member->full_name }} ({{ $loan->member->registration_no }})
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Loan Amount') }}</dt>
                        <dd>{{ number_format($loan->amount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Approved Amount') }}</dt>
                        <dd>{{ $loan->approved_amount ? number_format($loan->approved_amount, 2) : __('N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Balance') }}</dt>
                        <dd class="text-lg font-semibold">{{ number_format($loan->balance, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Status') }}</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $loan->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                {{ $loan->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                {{ $loan->status === 'disbursed' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                {{ $loan->status === 'repaid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $loan->status === 'defaulted' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
                            </span>
                        </dd>
                    </div>
                    @if($loan->purpose)
                        <div>
                            <dt class="font-medium">{{ __('Purpose') }}</dt>
                            <dd>{{ $loan->purpose }}</dd>
                        </div>
                    @endif
                    @if($loan->remarks)
                        <div>
                            <dt class="font-medium">{{ __('Remarks') }}</dt>
                            <dd>{{ $loan->remarks }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <h3 class="mb-4 font-semibold">{{ __('Loan Timeline') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="font-medium">{{ __('Applied On') }}</dt>
                        <dd>{{ $loan->created_at->format('F j, Y g:i A') }}</dd>
                    </div>
                    @if($loan->approved_at)
                        <div>
                            <dt class="font-medium">{{ __('Approved On') }}</dt>
                            <dd>{{ $loan->approved_at->format('F j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ __('Approved By') }}</dt>
                            <dd>{{ $loan->approvedBy?->name ?? __('N/A') }}</dd>
                        </div>
                    @endif
                    @if($loan->disbursed_at)
                        <div>
                            <dt class="font-medium">{{ __('Disbursed On') }}</dt>
                            <dd>{{ $loan->disbursed_date?->format('F j, Y') ?? $loan->disbursed_at->format('F j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ __('Disbursed By') }}</dt>
                            <dd>{{ $loan->disbursedBy?->name ?? __('N/A') }}</dd>
                        </div>
                    @endif
                    @if($loan->interest_rate > 0)
                        <div>
                            <dt class="font-medium">{{ __('Interest Rate') }}</dt>
                            <dd>{{ number_format($loan->interest_rate, 2) }}%</dd>
                        </div>
                    @endif
                    @if($loan->due_date)
                        <div>
                            <dt class="font-medium">{{ __('Due Date') }}</dt>
                            <dd>{{ $loan->due_date->format('F j, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                <h3 class="font-semibold">{{ __('Repayments') }}</h3>
            </div>
            <div class="overflow-x-auto p-4">
                @if($this->repayments->count() > 0)
                    <table class="w-full">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Method') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Reference') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Recorded By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->repayments as $repayment)
                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                    <td class="px-4 py-3 text-sm">{{ $repayment->payment_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium">{{ number_format($repayment->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ ucfirst($repayment->payment_method) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $repayment->payment_ref ?? __('N/A') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $repayment->createdBy?->name ?? __('N/A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td class="px-4 py-3 text-right" colspan="1">{{ __('Total Repaid:') }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($this->repayments->sum('amount'), 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <p class="py-4 text-center text-sm text-neutral-500">{{ __('No repayments recorded yet.') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <flux:modal name="approve-loan-modal" :show="$showApproveModal" focusable class="max-w-lg">
        <form wire:submit="approve" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Approve Loan') }}</flux:heading>
                <flux:subheading>{{ __('Set the approved amount and loan terms.') }}</flux:subheading>
            </div>

            <flux:input wire:model="approvedAmount" type="number" step="0.01" :label="__('Approved Amount')" required />
            <flux:input wire:model="interestRate" type="number" step="0.01" min="0" max="100" :label="__('Interest Rate (%)')" />
            <flux:input wire:model="repaymentPeriodMonths" type="number" min="1" :label="__('Repayment Period (Months)')" />
            <flux:input wire:model="dueDate" type="date" :label="__('Due Date')" />
            <flux:textarea wire:model="approveRemarks" :label="__('Remarks')" rows="3" />

            <div class="flex items-center gap-2 justify-end">
                <flux:modal.close>
                    <flux:button type="button" wire:click="closeApproveModal" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ __('Approve') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Disburse Modal -->
    <flux:modal name="disburse-loan-modal" :show="$showDisburseModal" focusable class="max-w-lg">
        <form wire:submit="disburse" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Disburse Loan') }}</flux:heading>
                <flux:subheading>{{ __('Record the loan disbursement date.') }}</flux:subheading>
            </div>

            <flux:input wire:model="disburseDate" type="date" :label="__('Disbursement Date')" required />
            <flux:textarea wire:model="disburseRemarks" :label="__('Remarks')" rows="3" />

            <div class="flex items-center gap-2 justify-end">
                <flux:modal.close>
                    <flux:button type="button" wire:click="closeDisburseModal" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ __('Disburse') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Repayment Modal -->
    <flux:modal name="repayment-modal" :show="$showRepaymentModal" focusable class="max-w-lg">
        <form wire:submit="recordRepayment" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Record Repayment') }}</flux:heading>
                <flux:subheading>{{ __('Record a loan repayment. Current balance:') }} {{ number_format($loan->balance, 2) }}</flux:subheading>
            </div>

            <flux:input wire:model="repaymentAmount" type="number" step="0.01" :label="__('Amount')" required />
            <flux:input wire:model="repaymentDate" type="date" :label="__('Payment Date')" required />
            <flux:select wire:model="repaymentMethod" :label="__('Payment Method')" required>
                <option value="cash">{{ __('Cash') }}</option>
                <option value="transfer">{{ __('Transfer') }}</option>
            </flux:select>
            <flux:input wire:model="repaymentRef" :label="__('Payment Reference')" maxlength="100" placeholder="{{ __('Optional - Payment reference number') }}" />
            <flux:file wire:model="repaymentReceipt" :label="__('Receipt')" accept="image/*,application/pdf" />
            <flux:text class="text-xs text-neutral-500">{{ __('Maximum file size: 5MB. Allowed formats: JPG, PNG, PDF') }}</flux:text>
            <flux:textarea wire:model="repaymentNotes" :label="__('Notes')" rows="3" />

            <div class="flex items-center gap-2 justify-end">
                <flux:modal.close>
                    <flux:button type="button" wire:click="closeRepaymentModal" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ __('Record') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Mark as Defaulted Modal -->
    <flux:modal name="default-modal" :show="$showDefaultModal" focusable class="max-w-lg">
        <form wire:submit="markAsDefaulted" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Mark as Defaulted') }}</flux:heading>
                <flux:subheading>{{ __('Mark this loan as defaulted. This action cannot be undone easily.') }}</flux:subheading>
            </div>

            <flux:textarea wire:model="defaultReason" :label="__('Reason')" rows="3" placeholder="{{ __('Optional - Reason for marking as defaulted') }}" />

            <div class="flex items-center gap-2 justify-end">
                <flux:modal.close>
                    <flux:button type="button" wire:click="closeDefaultModal" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">
                    {{ __('Mark as Defaulted') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

