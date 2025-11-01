<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LoanRepayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_ref',
        'receipt_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fundLedgerEntry(): MorphOne
    {
        return $this->morphOne(FundLedger::class, 'reference', 'reference_type', 'reference_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (LoanRepayment $repayment): void {
            session()->flash('success', __('Loan repayment recorded successfully.'));
        });

        static::updated(function (LoanRepayment $repayment): void {
            session()->flash('success', __('Loan repayment updated successfully.'));
        });

        static::deleted(function (LoanRepayment $repayment): void {
            session()->flash('success', __('Loan repayment deleted successfully.'));
        });
    }
}
