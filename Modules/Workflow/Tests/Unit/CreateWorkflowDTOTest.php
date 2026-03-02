<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Modules\Workflow\Application\DTOs\CreateWorkflowDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateWorkflowDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateWorkflowDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'Sales Order Approval',
            'entity_type' => 'sales_order',
        ]);

        $this->assertSame('Sales Order Approval', $dto->name);
        $this->assertSame('sales_order', $dto->entityType);
        $this->assertNull($dto->description);
        $this->assertTrue($dto->isActive);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'PO Approval',
            'entity_type' => 'purchase_order',
            'description' => 'Three-level approval chain',
            'is_active'   => false,
        ]);

        $this->assertSame('Three-level approval chain', $dto->description);
        $this->assertFalse($dto->isActive);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'Default Workflow',
            'entity_type' => 'invoice',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_description_defaults_to_null(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'Minimal Workflow',
            'entity_type' => 'crm_lead',
        ]);

        $this->assertNull($dto->description);
    }
}
