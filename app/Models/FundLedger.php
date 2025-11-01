<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class FundLedger extends Model
{
    public $timestamps = true;

    protected $table = 'fund_ledger';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isInflow(): bool
    {
        return $this->type === 'inflow';
    }

    public function isOutflow(): bool
    {
        return $this->type === 'outflow';
    }
}
