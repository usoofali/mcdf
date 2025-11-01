<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

final class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['admin', 'finance']) || $loan->member_id === auth()->id();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    public function disburse(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    public function recordRepayment(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->hasRole('admin') && $loan->status === 'pending';
    }

    public function restore(User $user, Loan $loan): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->hasRole('admin');
    }
}
