<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class State extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
