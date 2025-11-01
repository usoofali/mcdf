<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

final class ContributionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance', 'health']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contribution $contribution): bool
    {
        return $user->hasAnyRole(['admin', 'finance', 'health']);
    }

    /**
     * Determine whether the user can create models.
     * Staff (Finance/Admin) can record contributions for members.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    /**
     * Determine whether the user can submit contributions.
     * Members can submit their own contributions.
     */
    public function submit(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance', 'member']);
    }

    /**
     * Determine whether the user can review contributions.
     */
    public function review(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contribution $contribution): bool
    {
        return $user->hasAnyRole(['admin', 'finance']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contribution $contribution): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contribution $contribution): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contribution $contribution): bool
    {
        return $user->hasRole('admin');
    }
}
