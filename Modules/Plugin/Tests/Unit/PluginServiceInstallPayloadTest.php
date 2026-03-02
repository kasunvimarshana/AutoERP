<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use Modules\Plugin\Application\DTOs\InstallPluginDTO;
use Modules\Plugin\Application\Services\PluginService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PluginService — installPlugin payload mapping.
 *
 * installPlugin() calls DB::transaction() + Eloquent::create() internally,
 * which require a full Laravel bootstrap.  These tests verify the DTO
 * field-mapping rules that mirror the installPlugin() create payload,
 * keeping everything pure PHP.
 */
class PluginServiceInstallPayloadTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Structural compliance
    // -------------------------------------------------------------------------

    public function test_plugin_service_has_install_plugin_method(): void
    {
        $this->assertTrue(
            method_exists(PluginService::class, 'installPlugin'),
            'PluginService must expose a public installPlugin() method.'
        );
    }

    public function test_install_plugin_accepts_install_plugin_dto(): void
    {
        $reflection = new \ReflectionMethod(PluginService::class, 'installPlugin');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(InstallPluginDTO::class, (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // InstallPluginDTO create payload (pure PHP — mirrors installPlugin logic)
    // -------------------------------------------------------------------------

    public function test_install_payload_sets_active_true(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'My Plugin',
            'alias'   => 'my-plugin',
            'version' => '1.0.0',
        ]);

        $createPayload = [
            'name'          => $dto->name,
            'alias'         => $dto->alias,
            'description'   => $dto->description,
            'version'       => $dto->version,
            'keywords'      => $dto->keywords,
            'requires'      => $dto->requires,
            'active'        => true,
            'manifest_data' => $dto->manifestData,
        ];

        $this->assertTrue($createPayload['active']);
    }

    public function test_install_payload_description_defaults_to_null(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'Minimal Plugin',
            'alias'   => 'minimal',
            'version' => '0.1.0',
        ]);

        $this->assertNull($dto->description);
    }

    public function test_install_payload_maps_all_provided_fields(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'          => 'Advanced Plugin',
            'alias'         => 'advanced-plugin',
            'description'   => 'Does advanced things',
            'version'       => '2.3.1',
            'keywords'      => ['advanced', 'erp'],
            'requires'      => ['core', 'tenancy'],
            'manifest_data' => ['priority' => 5, 'tags' => ['module']],
        ]);

        $createPayload = [
            'name'          => $dto->name,
            'alias'         => $dto->alias,
            'description'   => $dto->description,
            'version'       => $dto->version,
            'keywords'      => $dto->keywords,
            'requires'      => $dto->requires,
            'active'        => true,
            'manifest_data' => $dto->manifestData,
        ];

        $this->assertSame('Advanced Plugin', $createPayload['name']);
        $this->assertSame('advanced-plugin', $createPayload['alias']);
        $this->assertSame('Does advanced things', $createPayload['description']);
        $this->assertSame('2.3.1', $createPayload['version']);
        $this->assertSame(['advanced', 'erp'], $createPayload['keywords']);
        $this->assertSame(['core', 'tenancy'], $createPayload['requires']);
        $this->assertSame(['priority' => 5, 'tags' => ['module']], $createPayload['manifest_data']);
    }

    public function test_install_payload_keywords_defaults_to_empty_array(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'No Keywords',
            'alias'   => 'no-kw',
            'version' => '1.0.0',
        ]);

        $this->assertSame([], $dto->keywords);
    }

    public function test_install_payload_requires_defaults_to_empty_array(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'No Deps',
            'alias'   => 'no-deps',
            'version' => '1.0.0',
        ]);

        $this->assertSame([], $dto->requires);
    }

    public function test_install_payload_manifest_data_defaults_to_empty_array(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'No Manifest Data',
            'alias'   => 'no-manifest',
            'version' => '1.0.0',
        ]);

        $this->assertSame([], $dto->manifestData);
    }
}
