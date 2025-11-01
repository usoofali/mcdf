<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Contribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'contribution_plan_id',
        'amount',
        'payment_method',
        'payment_ref',
        'payment_date',
        'receipt_path',
        'status',
        'recorded_by',
        'reviewed_by',
        'reviewed_at',
        'fine_amount',
        'receipt_notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'reviewed_at' => 'datetime',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ContributionPlan::class, 'contribution_plan_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fundLedgerEntry(): MorphOne
    {
        return $this->morphOne(FundLedger::class, 'reference', 'reference_type', 'reference_id');
    }

    public function isPendingReview(): bool
    {
        return in_array($this->status, ['submitted', 'pending_review']);
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'paid']);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Contribution $contribution): void {
            if ($contribution->status === 'paid' && $contribution->recorded_by) {
                session()->flash('success', __('Contribution recorded successfully.'));
            } elseif ($contribution->status === 'pending_review') {
                session()->flash('success', __('Contribution submitted successfully. It will be reviewed shortly.'));
            }
        });

        static::updated(function (Contribution $contribution): void {
            if ($contribution->wasChanged('status')) {
                if ($contribution->status === 'paid') {
                    session()->flash('success', __('Contribution approved successfully.'));
                } elseif ($contribution->status === 'rejected') {
                    session()->flash('info', __('Contribution has been rejected.'));
                }
            }
        });

        static::deleted(function (Contribution $contribution): void {
            session()->flash('success', __('Contribution deleted successfully.'));
        });
    }
}
