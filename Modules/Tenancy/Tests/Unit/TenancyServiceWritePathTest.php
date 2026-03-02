<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Modules\Tenancy\Application\DTOs\CreateTenantDTO;
use Modules\Tenancy\Application\Services\TenancyService;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for TenancyService write-path methods.
 *
 * create(), update(), and delete() call DB::transaction() internally,
 * which requires a full Laravel bootstrap, so functional tests live in
 * feature tests. These pure-PHP tests verify method signatures and
 * DTO field-mapping contracts.
 */
class TenancyServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_tenancy_service_has_create_method(): void
    {
        $this->assertTrue(
            method_exists(TenancyService::class, 'create'),
            'TenancyService must expose a public create() method.'
        );
    }

    public function test_tenancy_service_has_update_method(): void
    {
        $this->assertTrue(
            method_exists(TenancyService::class, 'update'),
            'TenancyService must expose a public update() method.'
        );
    }

    public function test_tenancy_service_has_delete_method(): void
    {
        $this->assertTrue(
            method_exists(TenancyService::class, 'delete'),
            'TenancyService must expose a public delete() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_accepts_create_tenant_dto(): void
    {
        $reflection = new \ReflectionMethod(TenancyService::class, 'create');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateTenantDTO::class, (string) $params[0]->getType());
    }

    public function test_update_accepts_id_and_data_array(): void
    {
        $reflection = new \ReflectionMethod(TenancyService::class, 'update');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_delete_accepts_single_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(TenancyService::class, 'delete');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // CreateTenantDTO — create payload mapping
    // -------------------------------------------------------------------------

    public function test_create_payload_maps_dto_fields_correctly(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'                   => 'Acme Corporation',
            'slug'                   => 'acme',
            'domain'                 => 'acme.example.com',
            'is_active'              => true,
            'pharma_compliance_mode' => false,
        ]);

        $createPayload = $dto->toArray();

        $this->assertSame('Acme Corporation', $createPayload['name']);
        $this->assertSame('acme', $createPayload['slug']);
        $this->assertSame('acme.example.com', $createPayload['domain']);
        $this->assertTrue($createPayload['is_active']);
        $this->assertFalse($createPayload['pharma_compliance_mode']);
    }

    public function test_create_payload_null_domain_preserved(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name' => 'Simple Tenant',
            'slug' => 'simple',
        ]);

        $createPayload = $dto->toArray();

        $this->assertNull($createPayload['domain']);
        $this->assertTrue($createPayload['is_active']);
    }

    public function test_create_payload_pharma_compliance_mode_true_preserved(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'                   => 'PharmaLtd',
            'slug'                   => 'pharmaltd',
            'pharma_compliance_mode' => true,
        ]);

        $createPayload = $dto->toArray();

        $this->assertTrue($createPayload['pharma_compliance_mode']);
    }

    public function test_create_payload_is_active_false_preserved(): void
    {
        $dto = CreateTenantDTO::fromArray([
            'name'      => 'Inactive Corp',
            'slug'      => 'inactive',
            'is_active' => false,
        ]);

        $createPayload = $dto->toArray();

        $this->assertFalse($createPayload['is_active']);
    }
}
