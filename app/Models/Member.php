<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Member extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'registration_no',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'state_id',
        'lga_id',
        'status',
        'registration_date',
        'eligibility_start_date',
        'nin',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registration_date' => 'date',
            'eligibility_start_date' => 'date',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);

        return implode(' ', $parts);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Member $member): void {
            session()->flash('success', __('Member created successfully.'));
        });

        static::updated(function (Member $member): void {
            session()->flash('success', __('Member updated successfully.'));
        });

        static::deleted(function (Member $member): void {
            session()->flash('success', __('Member deleted successfully.'));
        });
    }
}
