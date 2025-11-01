<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contribution;
use App\Models\ContributionPlan;
use App\Models\FundLedger;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ContributionService
{
    /**
     * Submit a contribution by a member.
     */
    public function submit(Member $member, array $data, ?UploadedFile $receipt = null): Contribution
    {
        DB::beginTransaction();
        try {
            // Validate payment reference uniqueness if provided
            if (! empty($data['payment_ref'])) {
                $this->validatePaymentRef($data['payment_ref']);
            }

            // Check for duplicates
            if ($this->isDuplicate($member->id, $data)) {
                throw new \Exception('A similar contribution already exists within the last 3 days.');
            }

            // Store receipt if provided
            $receiptPath = null;
            if ($receipt) {
                $receiptPath = $this->storeReceipt($receipt);
            }

            // Create contribution
            $contribution = Contribution::create([
                'member_id' => $member->id,
                'contribution_plan_id' => $data['contribution_plan_id'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_ref' => $data['payment_ref'] ?? null,
                'payment_date' => $data['payment_date'],
                'receipt_path' => $receiptPath,
                'status' => 'pending_review',
                'receipt_notes' => $data['receipt_notes'] ?? null,
            ]);

            DB::commit();

            return $contribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record a contribution by staff (bypasses review).
     */
    public function record(Member $member, User $staffUser, array $data, ?UploadedFile $receipt = null): Contribution
    {
        DB::beginTransaction();
        try {
            // Validate payment reference uniqueness if provided
            if (! empty($data['payment_ref'])) {
                $this->validatePaymentRef($data['payment_ref']);
            }

            // Check for duplicates
            if ($this->isDuplicate($member->id, $data)) {
                throw new \Exception('A similar contribution already exists within the last 3 days.');
            }

            // Store receipt if provided (optional for staff recordings)
            $receiptPath = null;
            if ($receipt) {
                $receiptPath = $this->storeReceipt($receipt);
            }

            // Create contribution with approved status
            $contribution = Contribution::create([
                'member_id' => $member->id,
                'contribution_plan_id' => $data['contribution_plan_id'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_ref' => $data['payment_ref'] ?? null,
                'payment_date' => $data['payment_date'],
                'receipt_path' => $receiptPath,
                'status' => 'paid',
                'recorded_by' => $staffUser->id,
                'fine_amount' => $this->calculateFine($data['payment_date'], $data['amount']),
                'receipt_notes' => $data['receipt_notes'] ?? null,
            ]);

            // Create fund ledger entry
            $this->createLedgerEntry($contribution, $staffUser);

            DB::commit();

            return $contribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Review a contribution (approve or reject).
     */
    public function review(Contribution $contribution, User $reviewer, string $decision, ?string $reason = null): void
    {
        DB::beginTransaction();
        try {
            if ($decision === 'approve') {
                $contribution->update([
                    'status' => 'paid',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'fine_amount' => $this->calculateFine($contribution->payment_date, $contribution->amount),
                ]);

                // Create fund ledger entry
                $this->createLedgerEntry($contribution, $reviewer);
            } else {
                $contribution->update([
                    'status' => 'rejected',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => $reason,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if a contribution is a duplicate.
     */
    public function isDuplicate(int $memberId, array $data): bool
    {
        // If payment_ref is provided, check by payment_ref
        if (! empty($data['payment_ref'])) {
            return Contribution::where('payment_ref', $data['payment_ref'])->exists();
        }

        // Otherwise, check by member_id, payment_date, and amount within 3-day window
        $startDate = \Carbon\Carbon::parse($data['payment_date'])->subDays(3);
        $endDate = \Carbon\Carbon::parse($data['payment_date'])->addDays(3);

        return Contribution::where('member_id', $memberId)
            ->where('payment_date', $data['payment_date'])
            ->where('amount', $data['amount'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->exists();
    }

    /**
     * Store receipt file.
     */
    private function storeReceipt(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/receipts', $filename);

        return $path;
    }

    /**
     * Validate payment reference uniqueness.
     */
    private function validatePaymentRef(?string $paymentRef): void
    {
        if ($paymentRef && Contribution::where('payment_ref', $paymentRef)->exists()) {
            throw new \Exception('Payment reference already exists.');
        }
    }

    /**
     * Calculate fine amount (placeholder - implement business logic).
     */
    private function calculateFine(string $paymentDate, float $amount): float
    {
        // TODO: Implement fine calculation logic based on business rules
        return 0.00;
    }

    /**
     * Create fund ledger entry for contribution.
     */
    private function createLedgerEntry(Contribution $contribution, User $user): void
    {
        FundLedger::create([
            'reference_type' => Contribution::class,
            'reference_id' => $contribution->id,
            'type' => 'inflow',
            'amount' => $contribution->amount,
            'description' => "Contribution from {$contribution->member->full_name} - {$contribution->plan->name}",
            'transaction_date' => $contribution->payment_date,
            'created_by' => $user->id,
        ]);
    }
}

