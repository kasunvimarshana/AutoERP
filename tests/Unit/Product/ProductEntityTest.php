<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use InvalidArgumentException;
use Modules\Product\Domain\Entities\Product;
use Tests\TestCase;

class ProductEntityTest extends TestCase
{
    public function test_constructor_rejects_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported product type.');

        new Product(
            tenantId: 1,
            type: 'bundle',
            name: 'Widget',
            slug: 'widget',
            baseUomId: 1,
        );
    }

    public function test_constructor_rejects_non_positive_uom_conversion_factor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UOM conversion factor must be greater than zero.');

        new Product(
            tenantId: 1,
            type: 'physical',
            name: 'Widget',
            slug: 'widget',
            baseUomId: 1,
            uomConversionFactor: '0',
        );
    }

    public function test_constructor_rejects_serial_tracking_with_batch_or_lot_tracking(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Serial-tracked products cannot be batch-tracked or lot-tracked.');

        new Product(
            tenantId: 1,
            type: 'physical',
            name: 'Widget',
            slug: 'widget',
            baseUomId: 1,
            isSerialTracked: true,
            isBatchTracked: true,
        );
    }

    public function test_constructor_requires_standard_cost_for_standard_valuation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Standard cost is required when valuation method is standard.');

        new Product(
            tenantId: 1,
            type: 'physical',
            name: 'Widget',
            slug: 'widget',
            baseUomId: 1,
            valuationMethod: 'standard',
            standardCost: null,
        );
    }

    public function test_update_applies_valid_state_changes(): void
    {
        $product = new Product(
            id: 10,
            tenantId: 1,
            type: 'physical',
            name: 'Widget',
            slug: 'widget',
            baseUomId: 1,
        );

        $product->update(
            type: 'service',
            name: 'Widget Service',
            slug: 'widget-service',
            baseUomId: 1,
            imagePath: null,
            taxGroupId: null,
            categoryId: null,
            brandId: null,
            orgUnitId: null,
            sku: 'WGT-SVC-01',
            description: 'Service variant',
            purchaseUomId: null,
            salesUomId: null,
            uomConversionFactor: '1.5',
            isBatchTracked: false,
            isLotTracked: false,
            isSerialTracked: false,
            valuationMethod: 'weighted_average',
            standardCost: null,
            incomeAccountId: null,
            cogsAccountId: null,
            inventoryAccountId: null,
            expenseAccountId: null,
            purchasePrice: '9.990000',
            salesPrice: '19.990000',
            isActive: true,
            metadata: ['channel' => 'online'],
        );

        $this->assertSame('service', $product->getType());
        $this->assertSame('1.5', $product->getUomConversionFactor());
        $this->assertSame('weighted_average', $product->getValuationMethod());
        $this->assertSame(['channel' => 'online'], $product->getMetadata());
    }
}
