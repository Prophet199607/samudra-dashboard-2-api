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

        // ====== 1. DEFINE PERMISSIONS ======
        $permissions = [
            // User Management
            'view users',
            'edit users',

            // Workflow Steps (10 Permissions for 10 Steps)
            'create order',          // Step 1
            'assign branch',         // Step 2
            'approve order',         // Step 3
            'sales order',           // Step 4
            'quotation',             // Step 5
            'deposit slip',          // Step 6
            'payment confirmation',  // Step 7
            'invoice',               // Step 8
            'collection receipt',    // Step 9
            'delivery',              // Step 10
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // ====== 2. CREATE ROLES ======
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // ====== 3. ASSIGN PERMISSIONS TO ROLES ======
        
        // Admin gets all permissions (handled via Gate::before, but syncing here keeps DB clean)
        $adminRole->syncPermissions(Permission::all());

        // Manager gets everything except user management
        $managerPermissions = Permission::where('name', '!=', 'edit users')->get();
        $managerRole->syncPermissions($managerPermissions);

        // User gets permissions for creating orders and basic steps (Adjust as needed)
        // For now, giving them 'create order' and 'deposit slip' as examples, or all step permissions?
        // Let's give them typical "Sales Rep" permissions: 
        // Create Order, Sales Order, Quotation, Deposit Slip, Collection Receipt
        $userPermissions = Permission::whereIn('name', [
            'create order',
            'sales order',
            'quotation',
            'deposit slip',
            'collection receipt'
        ])->get();
        $userRole->syncPermissions($userPermissions);

        // ====== 4. CREATE DEFAULT ADMIN USER ======
        $admin = User::firstOrCreate(
            ['name' => 'Super Admin'],
            [
                'password' => bcrypt('admin123'),
                'location' => '01',
            ]
        );
        
        $admin->assignRole($adminRole);

        $this->command->info('Default roles, permissions, and admin user created.');
    }
}
