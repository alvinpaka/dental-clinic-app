<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions
        $perms = [
            'manageClinicalTemplates',
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Assign to admin and dentist roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $dentist = Role::firstOrCreate(['name' => 'dentist', 'guard_name' => 'web']);
        foreach ($perms as $name) {
            $admin->givePermissionTo($name);
            $dentist->givePermissionTo($name);
        }
    }
}
