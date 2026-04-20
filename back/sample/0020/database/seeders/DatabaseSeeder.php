<?php

namespace Database\Seeders;

use App\Modules\Core\Domain\Models\Tenant;
use App\Modules\User\Domain\Models\User;
use App\Modules\Finance\Domain\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // Create a Tenant
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'status' => 'active'
        ]);

        // Create a User within that tenant
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@acme.com',
            'password' => Hash::make('password'),
            'user_type' => 'admin'
        ]);

        // Create Chart of Accounts for this tenant
        Account::create([
            'tenant_id' => $tenant->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'Asset'
        ]);

        Account::create([
            'tenant_id' => $tenant->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'Revenue'
        ]);
        
        Account::create([
            'tenant_id' => $tenant->id,
            'code' => '5000',
            'name' => 'Inventory Expense',
            'type' => 'Expense'
        ]);
    }
}
