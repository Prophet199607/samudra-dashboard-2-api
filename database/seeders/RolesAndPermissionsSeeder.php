<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // ====== CLEAR CACHE ======
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ====== CREATE PERMISSIONS ======
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);
        Permission::firstOrCreate(['name' => 'create orders']);
        Permission::firstOrCreate(['name' => 'view orders']);
        Permission::firstOrCreate(['name' => 'edit orders']);
        Permission::firstOrCreate(['name' => 'delete orders']);

        // ====== CREATE ROLES ======
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // ====== ASSIGN PERMISSIONS TO ROLES ======
        $adminRole->givePermissionTo(Permission::all()); // Admin gets all permissions

        $managerRole->givePermissionTo(['view users', 'view orders', 'create orders', 'edit orders']);

        $staffRole->givePermissionTo(['view orders', 'create orders']); // limited permissions

        // ====== CREATE DEFAULT ADMIN USER ======
        $admin = User::firstOrCreate( // change this to your real admin email
            [
                'name' => 'Super Admin',
                'password' => bcrypt('admin123'), // change password
                'location' => '01',
            ]
        );

        $admin->assignRole($adminRole);

        $this->command->info('Default roles, permissions, and admin user created.');
    }
}
