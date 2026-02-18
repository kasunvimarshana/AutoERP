<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Services\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module Registry Feature Tests
 *
 * Tests the module discovery, registration, and metadata system.
 */
class ModuleRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(ModuleRegistry::class);
    }

    /** @test */
    public function it_registers_core_modules()
    {
        $this->assertTrue($this->registry->has('core'));
        $this->assertTrue($this->registry->has('inventory'));
    }

    /** @test */
    public function it_returns_module_metadata()
    {
        $metadata = $this->registry->getMetadata('inventory');

        $this->assertNotNull($metadata);
        $this->assertEquals('inventory', $metadata['id']);
        $this->assertEquals('Inventory Management', $metadata['name']);
        $this->assertArrayHasKey('config', $metadata);
        $this->assertArrayHasKey('permissions', $metadata);
    }

    /** @test */
    public function it_returns_all_modules_metadata()
    {
        $allMetadata = $this->registry->getMetadata();

        $this->assertIsArray($allMetadata);
        $this->assertArrayHasKey('inventory', $allMetadata);
        $this->assertGreaterThan(0, count($allMetadata));
    }

    /** @test */
    public function it_filters_enabled_modules()
    {
        $enabled = $this->registry->enabled();

        foreach ($enabled as $module) {
            $this->assertTrue($module->isEnabled());
        }
    }

    /** @test */
    public function it_aggregates_all_permissions()
    {
        $permissions = $this->registry->getAllPermissions();

        $this->assertIsArray($permissions);
        $this->assertContains('inventory.products.view', $permissions);
        $this->assertContains('inventory.products.create', $permissions);
    }

    /** @test */
    public function it_returns_module_statistics()
    {
        $stats = $this->registry->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('disabled', $stats);
        $this->assertArrayHasKey('modules', $stats);
        $this->assertGreaterThan(0, $stats['total']);
    }
}
