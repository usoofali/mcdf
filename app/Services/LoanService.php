<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FundLedger;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class LoanService
{
    public function apply(Member $member, array $data): Loan
    {
        return DB::transaction(function () use ($member, $data) {
            return Loan::create([
                'member_id' => $member->id,
                'amount' => $data['amount'],
                'purpose' => $data['purpose'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'pending',
            ]);
        });
    }

    public function approve(Loan $loan, User $approver, array $data): Loan
    {
        return DB::transaction(function () use ($loan, $approver, $data) {
            $loan->update([
                'status' => 'approved',
                'approved_amount' => $data['approved_amount'] ?? $loan->amount,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'interest_rate' => $data['interest_rate'] ?? 0,
                'repayment_period_months' => $data['repayment_period_months'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'remarks' => $data['remarks'] ?? $loan->remarks,
            ]);

            return $loan->fresh();
        });
    }

    public function disburse(Loan $loan, User $disburser, array $data): Loan
    {
        if ($loan->status !== 'approved') {
            throw new \RuntimeException('Only approved loans can be disbursed.');
        }

        return DB::transaction(function () use ($loan, $disburser, $data) {
            $loan->update([
                'status' => 'disbursed',
                'disbursed_by' => $disburser->id,
                'disbursed_at' => now(),
                'disbursed_date' => $data['disbursed_date'] ?? now(),
            ]);

            // Create fund ledger outflow entry for disbursement
            FundLedger::create([
                'reference_type' => Loan::class,
                'reference_id' => $loan->id,
                'type' => 'outflow',
                'amount' => $loan->approved_amount ?? $loan->amount,
                'description' => "Loan disbursement to {$loan->member->full_name}",
                'transaction_date' => $loan->disbursed_date ?? now(),
                'created_by' => $disburser->id,
            ]);

            return $loan->fresh();
        });
    }

    public function recordRepayment(Loan $loan, User $recorder, array $data, ?UploadedFile $receipt = null): LoanRepayment
    {
        return DB::transaction(function () use ($loan, $recorder, $data, $receipt) {
            $receiptPath = null;
            if ($receipt) {
                $receiptPath = $receipt->store('receipts/loan-repayments', 'public');
            }

            $repayment = LoanRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'payment_ref' => $data['payment_ref'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $data['notes'] ?? null,
                'created_by' => $recorder->id,
            ]);

            // Create fund ledger inflow entry for repayment
            FundLedger::create([
                'reference_type' => LoanRepayment::class,
                'reference_id' => $repayment->id,
                'type' => 'inflow',
                'amount' => $repayment->amount,
                'description' => "Loan repayment from {$loan->member->full_name}",
                'transaction_date' => $repayment->payment_date,
                'created_by' => $recorder->id,
            ]);

            // Check if loan is fully repaid
            $loan->refresh();
            if ($loan->balance <= 0 && $loan->status !== 'repaid') {
                $loan->update(['status' => 'repaid']);
            }

            return $repayment;
        });
    }

    public function markAsDefaulted(Loan $loan, User $user, ?string $reason = null): Loan
    {
        return DB::transaction(function () use ($loan, $user, $reason) {
            $loan->update([
                'status' => 'defaulted',
                'remarks' => $reason ? ($loan->remarks . "\n\nDefaulted: " . $reason) : $loan->remarks,
            ]);

            return $loan->fresh();
        });
    }

    public function calculateBalance(Loan $loan): float
    {
        $approvedAmount = $loan->approved_amount ?? $loan->amount;
        $totalRepaid = $loan->repayments()->sum('amount');

        return max(0, $approvedAmount - $totalRepaid);
    }
}

