<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed user groups first
        $this->call([
            UserGroupSeeder::class,
            MenuSeeder::class,
            MasterDataMenuSeeder::class,
        ]);

        // Create default admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'group_id' => 1, // Super Admin group
            'is_active' => true,
        ]);
    }
}
