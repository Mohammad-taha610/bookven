<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'superadmin@bookven.test'],
            [
                'name' => 'Super Admin',
                'password' => 'SuperAdmin123!',
                'phone' => '+10000000001',
                'role' => UserRole::SuperAdmin,
            ]
        );
    }
}
