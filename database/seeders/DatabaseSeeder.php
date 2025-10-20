<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $receptionist = User::factory()->create([
            'name' => 'Receptionist User',
            'email' => 'receptionist@example.com',
            'password' => Hash::make('password'),
        ]);
        $receptionist->assignRole('receptionist');

        $dentist = User::factory()->create([
            'name' => 'Dentist User',
            'email' => 'dentist@example.com',
            'password' => Hash::make('password'),
        ]);
        $dentist->assignRole('dentist');

        $assistant = User::factory()->create([
            'name' => 'Assistant User',
            'email' => 'assistant@example.com',
            'password' => Hash::make('password'),
        ]);
        $assistant->assignRole('assistant');

        User::factory(5)->create()->each(function (User $user) {
            $user->assignRole('assistant');
        });
    }
}
