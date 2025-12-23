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
            'manage-clinics',
            'view-audit-logs',
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Assign to admin and dentist roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $dentist = Role::firstOrCreate(['name' => 'dentist', 'guard_name' => 'web']);
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        
        foreach ($perms as $name) {
            if ($name === 'manage-clinics') {
                $superAdmin->givePermissionTo($name);
            } elseif ($name === 'view-audit-logs') {
                $superAdmin->givePermissionTo($name);
                $admin->givePermissionTo($name);
            } else {
                $admin->givePermissionTo($name);
                $dentist->givePermissionTo($name);
            }
        }
    }
}
