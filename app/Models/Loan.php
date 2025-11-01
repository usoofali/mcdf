<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'amount',
        'approved_amount',
        'status',
        'purpose',
        'remarks',
        'approved_by',
        'approved_at',
        'disbursed_by',
        'disbursed_at',
        'disbursed_date',
        'interest_rate',
        'repayment_period_months',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'disbursed_date' => 'date',
            'interest_rate' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function fundLedgerEntry(): MorphOne
    {
        return $this->morphOne(FundLedger::class, 'reference', 'reference_type', 'reference_id');
    }

    public function getBalanceAttribute(): float
    {
        $approvedAmount = $this->approved_amount ?? $this->amount;
        $totalRepaid = $this->repayments()->sum('amount');

        return max(0, $approvedAmount - $totalRepaid);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'disbursed', 'repaid']);
    }

    public function isDisbursed(): bool
    {
        return in_array($this->status, ['disbursed', 'repaid', 'defaulted']);
    }

    public function isRepaid(): bool
    {
        return $this->status === 'repaid' || $this->balance <= 0;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Loan $loan): void {
            session()->flash('success', __('Loan application created successfully.'));
        });

        static::updated(function (Loan $loan): void {
            if ($loan->wasChanged('status')) {
                $message = match ($loan->status) {
                    'approved' => __('Loan approved successfully.'),
                    'disbursed' => __('Loan disbursed successfully.'),
                    'repaid' => __('Loan marked as repaid.'),
                    'defaulted' => __('Loan marked as defaulted.'),
                    default => __('Loan updated successfully.'),
                };
                session()->flash('success', $message);
            } else {
                session()->flash('success', __('Loan updated successfully.'));
            }
        });

        static::deleted(function (Loan $loan): void {
            session()->flash('success', __('Loan deleted successfully.'));
        });
    }
}
