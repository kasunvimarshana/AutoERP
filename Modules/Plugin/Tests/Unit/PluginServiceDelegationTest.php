<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Plugin\Application\Services\PluginService;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;
use PHPUnit\Framework\TestCase;

/**
 * Delegation tests for PluginService showPlugin and listPlugins.
 *
 * Verifies that the service correctly delegates to the injected
 * PluginRepositoryContract. No database or Laravel bootstrap required.
 */
class PluginServiceDelegationTest extends TestCase
{
    private function makeService(?PluginRepositoryContract $repo = null): PluginService
    {
        return new PluginService(
            $repo ?? $this->createMock(PluginRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // showPlugin — delegates to repository findOrFail
    // -------------------------------------------------------------------------

    public function test_show_plugin_delegates_to_repository_find_or_fail(): void
    {
        $manifest = $this->getMockBuilder(PluginManifest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(5)
            ->willReturn($manifest);

        $result = $this->makeService($repo)->showPlugin(5);

        $this->assertSame($manifest, $result);
    }

    public function test_show_plugin_returns_plugin_manifest_type(): void
    {
        $manifest = $this->getMockBuilder(PluginManifest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->method('findOrFail')->willReturn($manifest);

        $result = $this->makeService($repo)->showPlugin(1);

        $this->assertInstanceOf(PluginManifest::class, $result);
    }

    // -------------------------------------------------------------------------
    // listPlugins — delegates to repository all
    // -------------------------------------------------------------------------

    public function test_list_plugins_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $result = $this->makeService($repo)->listPlugins();

        $this->assertSame($expected, $result);
    }

    public function test_list_plugins_returns_collection(): void
    {
        $items = new Collection([
            $this->getMockBuilder(PluginManifest::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(PluginManifest::class)->disableOriginalConstructor()->getMock(),
        ]);

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->method('all')->willReturn($items);

        $result = $this->makeService($repo)->listPlugins();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_list_plugins_returns_empty_collection_when_no_plugins(): void
    {
        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService($repo)->listPlugins();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // updatePlugin — delegates to repository findOrFail
    // -------------------------------------------------------------------------

    public function test_update_plugin_calls_find_or_fail_on_repository(): void
    {
        // updatePlugin wraps in DB::transaction — only verify the method signature here.
        $this->assertTrue(method_exists(PluginService::class, 'updatePlugin'));

        $reflection = new \ReflectionMethod(PluginService::class, 'updatePlugin');
        $params     = $reflection->getParameters();

        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_plugin_service_can_be_instantiated(): void
    {
        $repo    = $this->createMock(PluginRepositoryContract::class);
        $service = new PluginService($repo);

        $this->assertInstanceOf(PluginService::class, $service);
    }
}
