<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use Modules\Plugin\Application\Services\PluginService;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for PluginService tenant enablement methods.
 *
 * enableForTenant() and disableForTenant() use DB::transaction() and Eloquent
 * internally, which require a full Laravel bootstrap.  These tests validate
 * the public API contract (method existence, signatures, return type) via
 * reflection without invoking the database.
 */
class PluginServiceEnablementTest extends TestCase
{
    // -------------------------------------------------------------------------
    // enableForTenant — method existence and signature
    // -------------------------------------------------------------------------

    public function test_plugin_service_has_enable_for_tenant_method(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'enableForTenant'),
            'PluginService must expose a public enableForTenant() method.'
        );
    }

    public function test_enable_for_tenant_is_public(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'enableForTenant');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_enable_for_tenant_accepts_plugin_manifest_id(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'enableForTenant');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('pluginManifestId', $params[0]->getName());
    }

    public function test_enable_for_tenant_parameter_is_int(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'enableForTenant');
        $params     = $reflection->getParameters();

        $this->assertSame('int', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // disableForTenant — method existence and signature
    // -------------------------------------------------------------------------

    public function test_plugin_service_has_disable_for_tenant_method(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'disableForTenant'),
            'PluginService must expose a public disableForTenant() method.'
        );
    }

    public function test_disable_for_tenant_is_public(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'disableForTenant');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_disable_for_tenant_accepts_plugin_manifest_id(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'disableForTenant');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('pluginManifestId', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // resolveDependencies — structural compliance
    // -------------------------------------------------------------------------

    public function test_resolve_dependencies_is_public(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'resolveDependencies');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_resolve_dependencies_accepts_array_parameter(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'resolveDependencies');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('requires', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_plugin_service_can_be_instantiated_with_repository(): void
    {
        $repo    = $this->createMock(PluginRepositoryContract::class);
        $service = new PluginService($repo);

        $this->assertInstanceOf(PluginService::class, $service);
    }
}
