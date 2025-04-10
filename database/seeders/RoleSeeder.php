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
        $roleAdmin = Role::create(['name' => self::ADMIN]);
        $roleUser = Role::create(['name' => self::USER]);

        $permissionViewAds = Permission::query()->firstOrCreate(['name' => 'view_ads']);
        $permissionAddAds = Permission::query()->firstOrCreate(['name' => 'add_ads']);
        $permissionEditAds = Permission::query()->firstOrCreate(['name' => 'edit_ads']);
        $permissionDeleteAds = Permission::query()->firstOrCreate(['name' => 'delete_ads']);

        $permissionDeleteAds = Permission::query()->firstOrCreate(['name' => 'view_owners']);

        $permissionViewPaymentHistory = Permission::query()->firstOrCreate(['name' => 'view_payment_history']);

        $permissionAdmin = [$permissionViewAds, $permissionAddAds, $permissionEditAds, $permissionDeleteAds];
        $permissionUser = [$permissionViewPaymentHistory];

        $roleAdmin->syncPermissions($permissionAdmin);
        $roleUser->syncPermissions($permissionUser);
    }
}
