<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ContributionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'amount',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ContributionPlan $plan): void {
            // If this plan is being set as active and there's a need to deactivate others
            // Add logic here if needed
        });

        static::created(function (ContributionPlan $plan): void {
            session()->flash('success', __('Contribution plan created successfully.'));
        });

        static::updating(function (ContributionPlan $plan): void {
            // Add any updating logic if needed
        });

        static::updated(function (ContributionPlan $plan): void {
            session()->flash('success', __('Contribution plan updated successfully.'));
        });

        static::deleted(function (ContributionPlan $plan): void {
            session()->flash('success', __('Contribution plan deleted successfully.'));
        });
    }
}
