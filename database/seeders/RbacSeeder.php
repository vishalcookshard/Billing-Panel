<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RbacSeeder extends Seeder
{
    public function run()
    {
        // Define permissions
        $perms = [
            'manage-pages',
            'manage-categories',
            'manage-plans',
            'manage-settings',
            'view-audit',
            'manage-roles'
        ];

        $permIds = [];
        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p], ['label' => ucwords(str_replace('-', ' ', $p))]);
            $permIds[] = $perm->id;
        }

        // Admin role gets everything
        $admin = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin->permissions()->syncWithoutDetaching($permIds);

        // Support role example
        $support = Role::firstOrCreate(['name' => 'support'], ['label' => 'Support']);
        $supportPerms = Permission::whereIn('name', ['manage-pages','view-audit'])->get()->pluck('id')->toArray();
        $support->permissions()->syncWithoutDetaching($supportPerms);
    }
}
