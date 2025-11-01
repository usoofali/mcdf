<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contribution;
use App\Models\Dependent;
use App\Models\Member;
use App\Models\Setting;
use Carbon\Carbon;

final class EligibilityService
{
    /**
     * Get the wait days setting (default 30 days).
     */
    public function getWaitDays(): int
    {
        return (int) Setting::get('eligibility_wait_days', 30);
    }

    /**
     * Check if a member is eligible for health benefits.
     *
     * Eligibility rules:
     * 1. Days since registration_date >= wait_days
     * 2. Contributions are up-to-date (has active contribution plan contributions)
     */
    public function checkMemberEligibility(Member $member): array
    {
        $waitDays = $this->getWaitDays();
        $daysSinceRegistration = Carbon::parse($member->registration_date)->diffInDays(now());

        $isEligible = true;
        $reasons = [];

        // Check registration period
        if ($daysSinceRegistration < $waitDays) {
            $isEligible = false;
            $reasons[] = __('Member has not completed the required wait period of :days days. Currently :current days since registration.', [
                'days' => $waitDays,
                'current' => $daysSinceRegistration,
            ]);
        }

        // Check member status
        if ($member->status !== 'active') {
            $isEligible = false;
            $reasons[] = __('Member status is not active. Current status: :status', ['status' => ucfirst($member->status)]);
        }

        // Check contributions (simple check - has at least one approved/paid contribution in the last 12 months)
        $hasRecentContributions = Contribution::where('member_id', $member->id)
            ->whereIn('status', ['approved', 'paid'])
            ->where('payment_date', '>=', now()->subYear())
            ->exists();

        if (!$hasRecentContributions) {
            $isEligible = false;
            $reasons[] = __('Member does not have approved contributions in the last 12 months.');
        }

        return [
            'eligible' => $isEligible,
            'reasons' => $reasons,
            'days_since_registration' => $daysSinceRegistration,
            'wait_days' => $waitDays,
            'member_status' => $member->status,
            'has_recent_contributions' => $hasRecentContributions,
        ];
    }

    /**
     * Check if a dependent is eligible for health benefits.
     *
     * Dependent eligibility:
     * - Inherits from member eligibility
     * - Children must be age <= 15
     */
    public function checkDependentEligibility(Dependent $dependent): array
    {
        $memberEligibility = $this->checkMemberEligibility($dependent->member);

        $isEligible = $memberEligibility['eligible'];
        $reasons = $memberEligibility['reasons'];

        // Additional check for children
        if ($dependent->relationship === 'child') {
            $age = $dependent->age;
            if ($age > 15) {
                $isEligible = false;
                $reasons[] = __('Child dependent is over 15 years old. Current age: :age years.', ['age' => $age]);
            }
        }

        return [
            'eligible' => $isEligible,
            'reasons' => $reasons,
            'dependent_age' => $dependent->age ?? null,
            'dependent_relationship' => $dependent->relationship,
            'member_eligible' => $memberEligibility['eligible'],
        ];
    }

    /**
     * Get eligibility explanation for display.
     */
    public function getEligibilityExplanation(array $eligibility): string
    {
        if ($eligibility['eligible']) {
            return __('This member/dependent is eligible for health benefits.');
        }

        return __('This member/dependent is not eligible for health benefits.') . ' ' . implode(' ', $eligibility['reasons']);
    }
}

