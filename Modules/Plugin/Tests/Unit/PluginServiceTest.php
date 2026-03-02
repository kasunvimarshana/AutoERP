<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use InvalidArgumentException;
use Modules\Plugin\Application\DTOs\InstallPluginDTO;
use Modules\Plugin\Application\Services\PluginService;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PluginService business logic.
 *
 * The repository is stubbed — no database or Laravel bootstrap required.
 * These tests exercise:
 *  - listPlugins() delegation
 *  - resolveDependencies() validation logic
 */
class PluginServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listPlugins — delegation to repository
    // -------------------------------------------------------------------------

    public function test_list_plugins_delegates_to_repository_all(): void
    {
        $expected = new \Illuminate\Database\Eloquent\Collection();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $service = new PluginService($repo);
        $result  = $service->listPlugins();

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // resolveDependencies — validation logic
    // -------------------------------------------------------------------------

    public function test_resolve_dependencies_returns_empty_array_for_no_requires(): void
    {
        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->expects($this->never())->method('findByAlias');

        $service  = new PluginService($repo);
        $resolved = $service->resolveDependencies([]);

        $this->assertSame([], $resolved);
    }

    public function test_resolve_dependencies_returns_manifests_for_valid_aliases(): void
    {
        $manifest = new PluginManifest();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByAlias')
            ->with('core')
            ->willReturn($manifest);

        $service  = new PluginService($repo);
        $resolved = $service->resolveDependencies(['core']);

        $this->assertCount(1, $resolved);
        $this->assertSame($manifest, $resolved[0]);
    }

    public function test_resolve_dependencies_resolves_multiple_aliases(): void
    {
        $manifestCore     = new PluginManifest();
        $manifestTenancy  = new PluginManifest();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->method('findByAlias')
            ->willReturnMap([
                ['core',    $manifestCore],
                ['tenancy', $manifestTenancy],
            ]);

        $service  = new PluginService($repo);
        $resolved = $service->resolveDependencies(['core', 'tenancy']);

        $this->assertCount(2, $resolved);
    }

    public function test_resolve_dependencies_throws_when_alias_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/missing-plugin/');

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->method('findByAlias')->willReturn(null);

        $service = new PluginService($repo);
        $service->resolveDependencies(['missing-plugin']);
    }

    public function test_resolve_dependencies_throws_on_first_missing_alias(): void
    {
        // 'core' exists, 'unknown' does not — should throw on 'unknown'
        $manifest = new PluginManifest();

        $repo = $this->createMock(PluginRepositoryContract::class);
        $repo->method('findByAlias')
            ->willReturnMap([
                ['core',    $manifest],
                ['unknown', null],
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unknown/');

        $service = new PluginService($repo);
        $service->resolveDependencies(['core', 'unknown']);
    }

    // -------------------------------------------------------------------------
    // InstallPluginDTO — field validation
    // -------------------------------------------------------------------------

    public function test_install_plugin_dto_hydrates_correctly(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'        => 'My Plugin',
            'alias'       => 'my-plugin',
            'description' => 'A test plugin',
            'version'     => '1.0.0',
            'keywords'    => ['plugin', 'test'],
            'requires'    => ['core'],
        ]);

        $this->assertSame('My Plugin', $dto->name);
        $this->assertSame('my-plugin', $dto->alias);
        $this->assertSame('1.0.0', $dto->version);
        $this->assertSame(['core'], $dto->requires);
    }

    public function test_install_plugin_dto_defaults_empty_requires(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'Simple Plugin',
            'alias'   => 'simple',
            'version' => '2.0.0',
        ]);

        $this->assertSame([], $dto->requires);
    }
}
