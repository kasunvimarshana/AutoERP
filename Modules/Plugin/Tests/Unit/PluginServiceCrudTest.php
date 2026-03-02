<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use Modules\Plugin\Application\Services\PluginService;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for the new PluginService CRUD methods.
 *
 * showPlugin(), uninstallPlugin(), and listTenantPlugins() use Eloquent
 * and DB::transaction() internally, so these tests validate the public API
 * contract (method existence, signatures, return types) via reflection
 * without invoking the database.
 */
class PluginServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // showPlugin — method existence and signature
    // -------------------------------------------------------------------------

    public function test_show_plugin_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'showPlugin'),
            'PluginService must expose a public showPlugin() method.'
        );
    }

    public function test_show_plugin_is_public(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'showPlugin');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_plugin_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'showPlugin');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_plugin_return_type_is_plugin_manifest(): void
    {
        $reflection  = new \ReflectionMethod(PluginService::class, 'showPlugin');
        $returnType  = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertStringContainsString('PluginManifest', (string) $returnType);
    }

    // -------------------------------------------------------------------------
    // uninstallPlugin — method existence and signature
    // -------------------------------------------------------------------------

    public function test_uninstall_plugin_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'uninstallPlugin'),
            'PluginService must expose a public uninstallPlugin() method.'
        );
    }

    public function test_uninstall_plugin_is_public(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'uninstallPlugin');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_uninstall_plugin_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'uninstallPlugin');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_uninstall_plugin_return_type_is_bool(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'uninstallPlugin');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('bool', (string) $returnType);
    }

    // -------------------------------------------------------------------------
    // listTenantPlugins — method existence and signature
    // -------------------------------------------------------------------------

    public function test_list_tenant_plugins_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'listTenantPlugins'),
            'PluginService must expose a public listTenantPlugins() method.'
        );
    }

    public function test_list_tenant_plugins_is_public(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'listTenantPlugins');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_tenant_plugins_has_no_required_parameters(): void
    {
        $reflection        = new \ReflectionMethod(PluginService::class, 'listTenantPlugins');
        $requiredParamCount = 0;

        foreach ($reflection->getParameters() as $param) {
            if (! $param->isOptional()) {
                $requiredParamCount++;
            }
        }

        $this->assertSame(0, $requiredParamCount);
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_repository_mock(): void
    {
        $repo    = $this->createMock(PluginRepositoryContract::class);
        $service = new PluginService($repo);

        $this->assertInstanceOf(PluginService::class, $service);
    }
}
