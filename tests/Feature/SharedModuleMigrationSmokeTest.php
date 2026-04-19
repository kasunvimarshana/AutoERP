<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SharedModuleMigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_reference_tables_remain_available_after_migrations(): void
    {
        $this->assertTrue(Schema::hasTable('countries'));
        $this->assertTrue(Schema::hasTable('currencies'));
        $this->assertTrue(Schema::hasTable('languages'));
        $this->assertTrue(Schema::hasTable('timezones'));
    }

    public function test_redundant_global_attachments_table_is_not_created(): void
    {
        $this->assertFalse(Schema::hasTable('attachments'));
    }
}
