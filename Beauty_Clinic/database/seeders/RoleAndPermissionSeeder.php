<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'add salon',
            'delete salon',
            'update salon info',
            'add admin',
            'delete admin',
            'update admin info',
            'view all admins',
            'view all salons',
            'add employee',
            'delete employee',
            'update employee info',
            'add product',
            'delete product',
            'update product details',
            'add service',
            'delete service',
            'update service details',
            'view all appointments',
            'view all bookings',
            'book an appointment',
            'cancel appointment',
            'update appointment details',
            'booking a product',
            'delete booking',
            'update the booking details',
            'view all products',
            'view all services',
            'search about service',
            'search about salon',
            'search about product',
            'view service',
            'view salon',
            'view product',
            'view all employees',
            'view employee',
            'view admin',
            'search about employee',
            'search about admin',
            'view cart',
            'view appointment',
            'show cart',
            'view all carts',
            'delete cart',
            'view all user appointments',
            'view all user booking'
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'super_admin']);
            Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'admin']);
            Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'customer']);
        }


        Role::updateOrCreate(['name' => 'super_admin', 'guard_name' => 'super_admin'])->givePermissionTo([
            'add salon',
            'delete salon',
            'update salon info',
            'add admin',
            'delete admin',
            'update admin info',
            'view all admins',
            'view all salons',
            'view all products',
            'view all services',
            'view all appointments',
            'view all bookings',
            'view cart',
            'view appointment',
            'view service',
            'view salon',
            'view product',
            'view all employees',
            'view employee',
            'view admin',
            'search about product',
            'search about salon',
            'search about service',
            'search about employee',
            'search about admin',
            'show cart',
            'view all carts',
            'view all user appointments',
            'view all user booking'





        ]);
        Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'admin'])->givePermissionTo([
            'add employee',
            'delete employee',
            'update employee info',
            'add product',
            'delete product',
            'update product details',
            'add service',
            'delete service',
            'update service details',
            'view all appointments',
            'view all bookings',
            'view service',
            'view all products',
            'view all services',
            'view product',
            'view all employees',
            'view employee',
            'search about product',
            'search about salon',
            'search about service',
            'search about employee',
            'view cart',
            'view appointment',
            'show cart',
            'view all carts',
            'view all user appointments',
            'view all user booking'




        ]);
        Role::updateOrCreate(['name' => 'customer', 'guard_name' => 'customer'])->givePermissionTo([
            'book an appointment',
            'cancel appointment',
            'update appointment details',
            'booking a product',
            'delete booking',
            'update the booking details',
            'view all salons',
            'view all products',
            'view all services',
            'search about service',
            'search about salon',
            'search about product',
            'view service',
            'view salon',
            'view product',
            'view all employees',
            'view employee',
            'search about employee',
            'view cart',
            'view appointment',
            'show cart',
            'view all carts',
            'delete cart',
            'view all appointments',


        ]);
    }
}
