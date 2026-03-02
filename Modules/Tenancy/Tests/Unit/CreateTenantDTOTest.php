<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Modules\Tenancy\Application\DTOs\CreateTenantDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateTenantDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateTenantDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name' => 'Acme Tenant',
            'slug' => 'acme',
        ]);

        $this->assertSame('Acme Tenant', $dto->name);
        $this->assertSame('acme', $dto->slug);
        $this->assertNull($dto->domain);
        $this->assertTrue($dto->isActive);
        $this->assertFalse($dto->pharmaComplianceMode);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'                   => 'Pharma Corp',
            'slug'                   => 'pharma-corp',
            'domain'                 => 'pharma.example.com',
            'is_active'              => true,
            'pharma_compliance_mode' => true,
        ]);

        $this->assertSame('Pharma Corp', $dto->name);
        $this->assertSame('pharma-corp', $dto->slug);
        $this->assertSame('pharma.example.com', $dto->domain);
        $this->assertTrue($dto->isActive);
        $this->assertTrue($dto->pharmaComplianceMode);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name' => 'Default Tenant',
            'slug' => 'default',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_pharma_compliance_mode_defaults_to_false(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name' => 'Standard Tenant',
            'slug' => 'standard',
        ]);

        $this->assertFalse($dto->pharmaComplianceMode);
    }

    public function test_is_active_can_be_false(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'      => 'Inactive Tenant',
            'slug'      => 'inactive',
            'is_active' => false,
        ]);

        $this->assertFalse($dto->isActive);
    }

    public function test_domain_defaults_to_null(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name' => 'No Domain Tenant',
            'slug' => 'no-domain',
        ]);

        $this->assertNull($dto->domain);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'                   => 'Array Test',
            'slug'                   => 'array-test',
            'domain'                 => 'test.example.com',
            'is_active'              => true,
            'pharma_compliance_mode' => false,
        ]);

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('domain', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('pharma_compliance_mode', $array);

        $this->assertSame('Array Test', $array['name']);
        $this->assertSame('array-test', $array['slug']);
        $this->assertSame('test.example.com', $array['domain']);
        $this->assertTrue($array['is_active']);
        $this->assertFalse($array['pharma_compliance_mode']);
    }

    public function test_boolean_coercion_from_string(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'                   => 'Coercion Test',
            'slug'                   => 'coercion',
            'is_active'              => '1',
            'pharma_compliance_mode' => '0',
        ]);

        $this->assertIsBool($dto->isActive);
        $this->assertIsBool($dto->pharmaComplianceMode);
        $this->assertTrue($dto->isActive);
        $this->assertFalse($dto->pharmaComplianceMode);
    }
}
