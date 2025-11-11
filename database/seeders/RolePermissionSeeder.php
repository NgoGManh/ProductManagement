<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ["admin", "user"];
        $permissions = ["view product", "create product", "edit product", "delete product"];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(["name" => $perm]);
        }

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(["name" => $roleName]);
            if ($roleName === "admin") {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->givePermissionTo("view product");
            }
        }
    }
}
