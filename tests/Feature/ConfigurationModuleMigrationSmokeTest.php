<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConfigurationModuleMigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_tables_exist_under_configuration_module_migrations(): void
    {
        $this->assertTrue(Schema::hasTable('countries'));
        $this->assertTrue(Schema::hasTable('currencies'));
        $this->assertTrue(Schema::hasTable('languages'));
        $this->assertTrue(Schema::hasTable('timezones'));
    }

    public function test_tenant_settings_table_exists_as_single_source_of_truth_for_config_persistence(): void
    {
        $this->assertTrue(Schema::hasTable('tenant_settings'));
    }

    public function test_redundant_module_configurations_table_is_not_created(): void
    {
        $this->assertFalse(Schema::hasTable('module_configurations'));
    }
}
