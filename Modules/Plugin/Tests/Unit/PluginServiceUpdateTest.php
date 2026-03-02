<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use Modules\Plugin\Application\Services\PluginService;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural unit tests for PluginService::updatePlugin().
 *
 * Validates method existence, visibility, parameter signature, and return type.
 * No DB required.
 */
class PluginServiceUpdateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence
    // -------------------------------------------------------------------------

    public function test_update_plugin_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'updatePlugin'),
            'PluginService must expose an updatePlugin() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Visibility
    // -------------------------------------------------------------------------

    public function test_update_plugin_is_public(): void
    {
        $ref = new ReflectionMethod(PluginService::class, 'updatePlugin');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // Parameter signature
    // -------------------------------------------------------------------------

    public function test_update_plugin_accepts_id_parameter(): void
    {
        $ref    = new ReflectionMethod(PluginService::class, 'updatePlugin');
        $params = $ref->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_update_plugin_accepts_data_array(): void
    {
        $ref    = new ReflectionMethod(PluginService::class, 'updatePlugin');
        $params = $ref->getParameters();

        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', $params[1]->getType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Return type
    // -------------------------------------------------------------------------

    public function test_update_plugin_return_type_is_plugin_manifest(): void
    {
        $ref = new ReflectionMethod(PluginService::class, 'updatePlugin');
        $this->assertSame(PluginManifest::class, $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_repository_contract(): void
    {
        $repository = $this->createStub(PluginRepositoryContract::class);
        $service    = new PluginService($repository);

        $this->assertInstanceOf(PluginService::class, $service);
    }
}
