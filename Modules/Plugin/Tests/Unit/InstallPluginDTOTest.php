<?php

declare(strict_types=1);

namespace Modules\Plugin\Tests\Unit;

use Modules\Plugin\Application\DTOs\InstallPluginDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InstallPluginDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class InstallPluginDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'Advanced Reporting',
            'alias'   => 'advanced-reporting',
            'version' => '1.0.0',
        ]);

        $this->assertSame('Advanced Reporting', $dto->name);
        $this->assertSame('advanced-reporting', $dto->alias);
        $this->assertSame('1.0.0', $dto->version);
        $this->assertNull($dto->description);
        $this->assertSame([], $dto->keywords);
        $this->assertSame([], $dto->requires);
        $this->assertSame([], $dto->manifestData);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'          => 'Pharma Compliance',
            'alias'         => 'pharma-compliance',
            'description'   => 'Adds FDA/DEA/DSCSA compliance features',
            'version'       => '2.1.0',
            'keywords'      => ['pharma', 'compliance', 'fda'],
            'requires'      => ['inventory', 'product'],
            'manifest_data' => ['author' => 'KV Team'],
        ]);

        $this->assertSame('Adds FDA/DEA/DSCSA compliance features', $dto->description);
        $this->assertSame(['pharma', 'compliance', 'fda'], $dto->keywords);
        $this->assertSame(['inventory', 'product'], $dto->requires);
        $this->assertSame(['author' => 'KV Team'], $dto->manifestData);
    }

    public function test_description_defaults_to_null(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'Minimal Plugin',
            'alias'   => 'minimal',
            'version' => '0.1.0',
        ]);

        $this->assertNull($dto->description);
    }

    public function test_requires_defaults_to_empty_array(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'Standalone Plugin',
            'alias'   => 'standalone',
            'version' => '1.0.0',
        ]);

        $this->assertSame([], $dto->requires);
    }

    public function test_keywords_defaults_to_empty_array(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'No Keywords Plugin',
            'alias'   => 'no-keywords',
            'version' => '1.0.0',
        ]);

        $this->assertSame([], $dto->keywords);
    }

    public function test_manifest_data_defaults_to_empty_array(): void
    {
        $dto = InstallPluginDTO::fromArray([
            'name'    => 'No Manifest Plugin',
            'alias'   => 'no-manifest',
            'version' => '1.0.0',
        ]);

        $this->assertSame([], $dto->manifestData);
    }
}
