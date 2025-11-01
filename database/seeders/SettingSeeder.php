<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'eligibility_wait_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Number of days a member must wait after registration before becoming eligible for health benefits',
                'group' => 'eligibility',
            ],
            [
                'key' => 'contribution_fine_rate',
                'value' => '5.00',
                'type' => 'decimal',
                'description' => 'Fine rate percentage for late contributions',
                'group' => 'financial',
            ],
            [
                'key' => 'loan_interest_rate',
                'value' => '10.00',
                'type' => 'decimal',
                'description' => 'Default interest rate percentage for loans',
                'group' => 'financial',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
