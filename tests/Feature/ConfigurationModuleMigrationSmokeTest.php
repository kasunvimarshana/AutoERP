<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConfigurationModuleMigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_settings_table_exists_as_single_source_of_truth_for_config_persistence(): void
    {
        $this->assertTrue(Schema::hasTable('tenant_settings'));
    }

    public function test_redundant_module_configurations_table_is_not_created(): void
    {
        $this->assertFalse(Schema::hasTable('module_configurations'));
    }
}
