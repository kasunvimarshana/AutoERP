<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\System;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DatabaseSeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_database_seeder_executes_cleanly_and_populates_core_ecosystem(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Assert core multi-tenant foundation was populated
        $this->assertDatabaseHas('tenants', ['status' => 'draft']);
        $this->assertGreaterThan(0, DB::table('tenants')->count());

        // Assert sequence and unit master data
        $this->assertGreaterThan(0, DB::table('sequences')->count());
        $this->assertGreaterThan(0, DB::table('unit_of_measures')->count());
        $this->assertGreaterThan(0, DB::table('uom_conversions')->count());

        // Assert financial chart of accounts and payment methods
        $this->assertGreaterThan(0, DB::table('finance_accounts')->count());
        $this->assertGreaterThan(0, DB::table('payment_methods')->count());

        // Assert item and warehouse master data
        $this->assertGreaterThan(0, DB::table('warehouses')->count());
        $this->assertGreaterThan(0, DB::table('items')->count());

        // Assert partner and asset master data
        $this->assertGreaterThan(0, DB::table('customers')->count());
        $this->assertGreaterThan(0, DB::table('suppliers')->count());
        $this->assertGreaterThan(0, DB::table('vehicles')->count());

        // Assert permissions catalog
        $this->assertGreaterThan(0, DB::table('permissions')->count());
    }
}
