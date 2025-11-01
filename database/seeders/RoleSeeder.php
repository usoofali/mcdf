<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access',
            ],
            [
                'name' => 'Finance Officer',
                'slug' => 'finance',
                'description' => 'Access to financial operations and contributions',
            ],
            [
                'name' => 'Health Officer',
                'slug' => 'health',
                'description' => 'Access to health eligibility and member records',
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Regular member access',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
