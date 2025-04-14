<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    const ADMIN = 'admin';
    const USER = 'user';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*        $roleAdmin = Role::query()->firstOrCreate(
            ['name' => self::ADMIN, 'guard_name' => 'web'],
            [
                'name' => self::ADMIN,
                'guard_name' => 'web'
            ]
        );
        $roleUser = Role::query()->firstOrCreate(
            ['name' => self::USER, 'guard_name' => 'web_user'],
            [
                'name' => self::USER,
                'guard_name' => 'web_user'
            ]
        );*/


        $roleAdmin = Role::create(['name' => self::ADMIN, 'guard_name' => 'web']);
        $roleUser = Role::create(['name' => self::USER, 'guard_name' => 'web_user']);

        $permissionViewAds = Permission::query()->firstOrCreate(['name' => 'view_ads', 'guard_name' => 'web']);
        $permissionAddAds = Permission::query()->firstOrCreate(['name' => 'add_ads', 'guard_name' => 'web']);
        $permissionEditAds = Permission::query()->firstOrCreate(['name' => 'edit_ads', 'guard_name' => 'web']);
        $permissionDeleteAds = Permission::query()->firstOrCreate(['name' => 'delete_ads', 'guard_name' => 'web']);

        $permissionDeleteAds = Permission::query()->firstOrCreate(['name' => 'view_owners', 'guard_name' => 'web']);

        $permissionViewPaymentHistory = Permission::query()->firstOrCreate(['name' => 'view_payment_history', 'guard_name' => 'web_user']);

        $permissionAdmin = [$permissionViewAds, $permissionAddAds, $permissionEditAds, $permissionDeleteAds];
        $permissionUser = [$permissionViewPaymentHistory];

        $roleAdmin->syncPermissions($permissionAdmin);
        $roleUser->syncPermissions($permissionUser);
    }
}
