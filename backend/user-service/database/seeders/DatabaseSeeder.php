<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()->instance('current_tenant_id', 1);

        User::create([
            'tenant_id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'roles' => ['admin'],
            'attributes' => ['department' => 'IT', 'region' => 'US', 'clearance_level' => 5],
            'is_active' => true,
        ]);
    }
}
