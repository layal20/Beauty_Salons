<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\SuperAdmin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SuperAdmin::all()->each(function ($super_admin) {
            $super_admin->assignRole("super_admin");
        });

        Admin::all()->each(function ($admin) {
            $admin->assignRole("admin");
        });

        Customer::all()->each(function ($customer) {
            $customer->assignRole("customer");
        });
    }
}

// $role1 = Role::findByName('admin', 'admin');
// $permissions1 = Permission::where('guard_name', 'admin')->get();

// foreach ($permissions1 as $permissionn) {
//     if (!$role1->hasPermissionTo($permissionn)) {
//         $role1->givePermissionTo($permissionn);
//     }
// }

// $role = Role::findByName('customer', 'customer');
// $permissions = Permission::where('guard_name', 'customer')->get();

// foreach ($permissions as $permission) {
//     if (!$role->hasPermissionTo($permission)) {
//         $role->givePermissionTo($permission);
//     }
// }
// $role2 = Role::findByName('super_admin', 'super_admin');
// $permissions2 = Permission::where('guard_name', 'super_admin')->get();

// foreach ($permissions2 as $permission2) {
//     if (!$role2->hasPermissionTo($permission2)) {
//         $role2->givePermissionTo($permission2);
//     }
// }


