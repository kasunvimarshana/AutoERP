<?php

declare(strict_types=1);

namespace Modules\Metadata\Tests\Unit;

use Modules\Metadata\Application\DTOs\CreateCustomFieldDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateCustomFieldDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateCustomFieldDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'product',
            'field_name'  => 'shelf_life_days',
            'field_label' => 'Shelf Life (Days)',
            'field_type'  => 'number',
        ]);

        $this->assertSame('product', $dto->entityType);
        $this->assertSame('shelf_life_days', $dto->fieldName);
        $this->assertSame('Shelf Life (Days)', $dto->fieldLabel);
        $this->assertSame('number', $dto->fieldType);
        $this->assertNull($dto->options);
        $this->assertFalse($dto->isRequired);
        $this->assertTrue($dto->isActive);
        $this->assertSame(0, $dto->sortOrder);
        $this->assertNull($dto->validationRules);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type'      => 'customer',
            'field_name'       => 'tier',
            'field_label'      => 'Customer Tier',
            'field_type'       => 'select',
            'options'          => ['bronze', 'silver', 'gold', 'platinum'],
            'is_required'      => true,
            'is_active'        => false,
            'sort_order'       => 5,
            'validation_rules' => ['in:bronze,silver,gold,platinum'],
        ]);

        $this->assertSame(['bronze', 'silver', 'gold', 'platinum'], $dto->options);
        $this->assertTrue($dto->isRequired);
        $this->assertFalse($dto->isActive);
        $this->assertSame(5, $dto->sortOrder);
        $this->assertSame(['in:bronze,silver,gold,platinum'], $dto->validationRules);
    }

    public function test_is_required_defaults_to_false(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'invoice',
            'field_name'  => 'po_number',
            'field_label' => 'PO Number',
            'field_type'  => 'text',
        ]);

        $this->assertFalse($dto->isRequired);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'invoice',
            'field_name'  => 'po_number',
            'field_label' => 'PO Number',
            'field_type'  => 'text',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_sort_order_defaults_to_zero(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'product',
            'field_name'  => 'dimension',
            'field_label' => 'Dimension',
            'field_type'  => 'text',
        ]);

        $this->assertSame(0, $dto->sortOrder);
    }

    public function test_sort_order_cast_to_int(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'product',
            'field_name'  => 'weight',
            'field_label' => 'Weight',
            'field_type'  => 'number',
            'sort_order'  => '10',
        ]);

        $this->assertIsInt($dto->sortOrder);
        $this->assertSame(10, $dto->sortOrder);
    }
}
