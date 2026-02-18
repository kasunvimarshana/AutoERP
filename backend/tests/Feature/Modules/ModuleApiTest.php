<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module Metadata API Tests
 *
 * Tests the module metadata API endpoints.
 */
class ModuleApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_all_module_metadata()
    {
        $response = $this->getJson('/api/modules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'version',
                        'dependencies',
                        'config',
                        'permissions',
                        'routes',
                        'enabled',
                    ],
                ],
                'statistics' => [
                    'total',
                    'enabled',
                    'disabled',
                    'modules',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_returns_specific_module_metadata()
    {
        $response = $this->getJson('/api/modules/inventory');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 'inventory',
                    'name' => 'Inventory Management',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_module()
    {
        $response = $this->getJson('/api/modules/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_all_module_routes()
    {
        $response = $this->getJson('/api/modules/routes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function it_returns_all_module_permissions()
    {
        $response = $this->getJson('/api/modules/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        $permissions = $response->json('data');
        $this->assertIsArray($permissions);
        $this->assertContains('inventory.products.view', $permissions);
    }
}
