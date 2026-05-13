<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // organisation / billing
            'manage organization', 'manage billing',
            // members
            'invite users', 'remove users', 'manage roles',
            // projects
            'create projects', 'delete projects', 'rotate api keys',
            // servers
            'register servers', 'delete servers',
            // threats
            'view threats', 'resolve threats', 'quarantine threats', 'investigate threats', 'mark false positive',
            // ops
            'view audit log', 'view horizon', 'view metrics',
            // 2FA & account
            'reset other users 2fa',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $matrix = [
            'superadmin' => $permissions,
            'admin' => array_diff($permissions, ['manage billing', 'manage roles', 'reset other users 2fa']),
            'developer' => [
                'view threats', 'resolve threats', 'investigate threats',
                'create projects', 'register servers',
            ],
            'viewer' => [
                'view threats', 'view metrics',
            ],
        ];

        foreach ($matrix as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
