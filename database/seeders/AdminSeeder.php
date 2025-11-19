<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            "name" => "admin",
            "guard_name" => "api",
            ]);
        $admin = User::firstOrCreate(
            ["email" => "admin@example.com"],
            [
                "first_name" => "Admin",
                "last_name" => "Root",
                "password" => bcrypt("12345678"),
                "status" => "ACTIVE",
            ],
        );

        $admin->assignRole($role);

        echo "✅ Admin user created: admin@example.com / 12345678\n";
    }
}
