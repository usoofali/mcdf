<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Dependent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'name',
        'date_of_birth',
        'relationship',
        'nin',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isEligible(): bool
    {
        // Child eligible if age ≤ 15
        if ($this->relationship === 'child') {
            return $this->age <= 15;
        }

        // Other relationships inherit from member eligibility
        return $this->member->status === 'active';
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? (int) $this->date_of_birth->diffInYears(now()) : 0;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Dependent $dependent): void {
            session()->flash('success', __('Dependent added successfully.'));
        });

        static::updated(function (Dependent $dependent): void {
            session()->flash('success', __('Dependent updated successfully.'));
        });

        static::deleted(function (Dependent $dependent): void {
            session()->flash('success', __('Dependent deleted successfully.'));
        });
    }
}
