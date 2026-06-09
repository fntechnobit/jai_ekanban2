<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserGroup;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Super Admin',
                'description' => 'Has full access to all features',
                'is_active' => true,
            ],
            [
                'name' => 'Admin',
                'description' => 'Has access to most features',
                'is_active' => true,
            ],
            [
                'name' => 'User',
                'description' => 'Regular user with limited access',
                'is_active' => true,
            ],
        ];

        foreach ($groups as $group) {
            // Idempotent: avoid creating duplicate groups when reseeding.
            UserGroup::firstOrCreate(
                ['name' => $group['name']],
                $group
            );
        }
    }
}
