<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $clientRole = Role::create(['name' => 'client']);

        // Create permissions
        $permissions = [
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'view users',
            'edit users',
            'delete users',
            'view dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to admin
        $adminRole->givePermissionTo($permissions);

        // Assign limited permissions to client
        $clientRole->givePermissionTo([
            'view products',
            'view categories',
            'create orders',
            'view orders',
        ]);
    }
} 