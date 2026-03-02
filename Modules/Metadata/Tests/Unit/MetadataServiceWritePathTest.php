<?php

declare(strict_types=1);

namespace Modules\Metadata\Tests\Unit;

use Modules\Metadata\Application\DTOs\CreateCustomFieldDTO;
use Modules\Metadata\Application\Services\MetadataService;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for MetadataService write-path methods.
 *
 * createField(), updateField(), and deleteField() call DB::transaction()
 * internally, which requires a full Laravel bootstrap, so functional tests
 * live in feature tests. These pure-PHP tests verify method signatures and
 * DTO field-mapping contracts.
 */
class MetadataServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_metadata_service_has_create_field_method(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'createField'),
            'MetadataService must expose a public createField() method.'
        );
    }

    public function test_metadata_service_has_update_field_method(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'updateField'),
            'MetadataService must expose a public updateField() method.'
        );
    }

    public function test_metadata_service_has_delete_field_method(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'deleteField'),
            'MetadataService must expose a public deleteField() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_field_accepts_create_custom_field_dto(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'createField');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateCustomFieldDTO::class, (string) $params[0]->getType());
    }

    public function test_update_field_accepts_id_and_data_array(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'updateField');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_delete_field_accepts_single_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'deleteField');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // CreateCustomFieldDTO — create payload mapping
    // -------------------------------------------------------------------------

    public function test_create_field_payload_maps_dto_fields_correctly(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type'  => 'product',
            'field_name'   => 'batch_number',
            'field_label'  => 'Batch Number',
            'field_type'   => 'text',
            'is_required'  => true,
            'sort_order'   => 5,
        ]);

        $createPayload = [
            'entity_type'      => $dto->entityType,
            'field_name'       => $dto->fieldName,
            'field_label'      => $dto->fieldLabel,
            'field_type'       => $dto->fieldType,
            'options'          => $dto->options,
            'is_required'      => $dto->isRequired,
            'is_active'        => $dto->isActive,
            'sort_order'       => $dto->sortOrder,
            'validation_rules' => $dto->validationRules,
        ];

        $this->assertSame('product', $createPayload['entity_type']);
        $this->assertSame('batch_number', $createPayload['field_name']);
        $this->assertSame('Batch Number', $createPayload['field_label']);
        $this->assertSame('text', $createPayload['field_type']);
        $this->assertTrue($createPayload['is_required']);
        $this->assertSame(5, $createPayload['sort_order']);
        $this->assertTrue($createPayload['is_active']);
    }

    public function test_create_field_payload_null_options_preserved(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'sales_order',
            'field_name'  => 'reference',
            'field_label' => 'Reference',
            'field_type'  => 'text',
        ]);

        $createPayload = [
            'options'          => $dto->options,
            'validation_rules' => $dto->validationRules,
            'sort_order'       => $dto->sortOrder,
        ];

        $this->assertNull($createPayload['options']);
        $this->assertNull($createPayload['validation_rules']);
        $this->assertSame(0, $createPayload['sort_order']);
    }

    public function test_create_field_is_active_defaults_to_true(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'customer',
            'field_name'  => 'vip_tier',
            'field_label' => 'VIP Tier',
            'field_type'  => 'select',
        ]);

        $this->assertTrue($dto->isActive);
    }
}
