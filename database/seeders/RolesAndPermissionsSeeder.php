<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // ====== CLEAR CACHE ======
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ====== 1. DEFINE PERMISSIONS ======
        // Create Groups
        $menuGroup = PermissionGroup::firstOrCreate(['name' => 'Menus']);
        $distWorkflowGroup = PermissionGroup::firstOrCreate(['name' => 'Distribution Workflow Steps']);
        $prevColWorkflowGroup = PermissionGroup::firstOrCreate(['name' => 'Previous collection Steps']);
        $userGroup = PermissionGroup::firstOrCreate(['name' => 'User management']);

        $permissions = [
            // Menus
            ['name' => 'view dashboard', 'group_id' => $menuGroup->id],
            ['name' => 'view distribution', 'group_id' => $menuGroup->id],
            ['name' => 'view previous collections', 'group_id' => $menuGroup->id],
            ['name' => 'view user management', 'group_id' => $menuGroup->id],
            ['name' => 'view permissions', 'group_id' => $menuGroup->id],

            // User Management
            ['name' => 'view users', 'group_id' => $userGroup->id],
            ['name' => 'create users', 'group_id' => $userGroup->id],
            ['name' => 'edit users', 'group_id' => $userGroup->id],
            ['name' => 'delete users', 'group_id' => $userGroup->id],
            ['name' => 'view roles', 'group_id' => $userGroup->id],
            ['name' => 'create roles', 'group_id' => $userGroup->id],
            ['name' => 'edit roles', 'group_id' => $userGroup->id],
            ['name' => 'delete roles', 'group_id' => $userGroup->id],

            // Distribution Workflow Steps
            ['name' => 'create order', 'group_id' => $distWorkflowGroup->id],          // Step 1
            ['name' => 'assign branch', 'group_id' => $distWorkflowGroup->id],         // Step 2
            ['name' => 'approve order', 'group_id' => $distWorkflowGroup->id],         // Step 3
            ['name' => 'deposit slip', 'group_id' => $distWorkflowGroup->id],          // Step 6
            ['name' => 'payment confirmation', 'group_id' => $distWorkflowGroup->id],  // Step 7
            ['name' => 'invoice', 'group_id' => $distWorkflowGroup->id],               // Step 8
            ['name' => 'collection receipt', 'group_id' => $distWorkflowGroup->id],    // Step 9
            ['name' => 'delivery', 'group_id' => $distWorkflowGroup->id],              // Step 10

            // Previous collection Steps
            ['name' => 'create collection', 'group_id' => $prevColWorkflowGroup->id],
            ['name' => 'pc deposit slip', 'group_id' => $prevColWorkflowGroup->id],          // Step 6
            ['name' => 'pc payment confirmation', 'group_id' => $prevColWorkflowGroup->id],  // Step 7
            ['name' => 'pc receipt', 'group_id' => $prevColWorkflowGroup->id],               // Step 8
            ['name' => 'pc cash in', 'group_id' => $prevColWorkflowGroup->id],               // Step 9
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                ['permission_group_id' => $permission['group_id']]
            );
        }

        // ====== 2. CREATE ROLES ======
        $superAdminRole = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'api']);

        // ====== 3. ASSIGN PERMISSIONS TO ROLES ======
        
        // Super admin gets all permissions (handled via Gate::before, but syncing here keeps DB clean)
        $superAdminRole->syncPermissions(Permission::all());

        // ====== 4. CREATE DEFAULT ADMIN USER ======
        $superAdmin = User::firstOrCreate(
            ['name' => 'Super Admin'],
            [
                'password' => bcrypt('onimta1+'),
                'location' => '01',
            ]
        );
        
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('Default roles, permissions, and admin user created.');
    }
}
