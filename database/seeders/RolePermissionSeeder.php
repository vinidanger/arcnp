<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissões granulares do time interno (admin). Clientes não usam
     * roles/permissions do Spatie — o acesso deles é escopado por
     * ownership (policies), controlado pelo campo users.type.
     */
    private const ADMIN_PERMISSIONS = [
        'manage_users',
        'manage_permissions',
        'manage_servers',
        'manage_clients',
        'manage_hosting_accounts',
        'manage_billing',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ADMIN_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::findOrCreate('admin');
        $adminRole->syncPermissions(self::ADMIN_PERMISSIONS);

        Role::findOrCreate('client');
    }
}
