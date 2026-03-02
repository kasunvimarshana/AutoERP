<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Interfaces\Http\Controllers\InventoryController;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for InventoryService::listStockItems() and
 * the corresponding InventoryController::listStockItems() endpoint.
 *
 * Validates method existence, visibility, signatures, and return types
 * without requiring database connectivity or full Laravel bootstrap.
 */
class InventoryServiceListStockItemsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // InventoryService::listStockItems — method existence and signature
    // -------------------------------------------------------------------------

    public function test_inventory_service_has_list_stock_items_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'listStockItems'),
            'InventoryService must expose a public listStockItems() method.'
        );
    }

    public function test_list_stock_items_is_public(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'listStockItems');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_stock_items_is_not_static(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'listStockItems');

        $this->assertFalse($reflection->isStatic());
    }

    public function test_list_stock_items_per_page_parameter_has_default(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'listStockItems');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('perPage', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertSame(15, $params[0]->getDefaultValue());
    }

    public function test_list_stock_items_return_type_is_paginator(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'listStockItems');
        $returnType = (string) $reflection->getReturnType();

        $this->assertStringContainsString('LengthAwarePaginator', $returnType);
    }

    // -------------------------------------------------------------------------
    // InventoryRepositoryContract::paginateStockItems — contract method
    // -------------------------------------------------------------------------

    public function test_repository_contract_has_paginate_stock_items_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Domain\Contracts\InventoryRepositoryContract::class, 'paginateStockItems'),
            'InventoryRepositoryContract must declare paginateStockItems().'
        );
    }

    public function test_inventory_repository_implements_paginate_stock_items(): void
    {
        $this->assertTrue(
            method_exists(\Modules\Inventory\Infrastructure\Repositories\InventoryRepository::class, 'paginateStockItems'),
            'InventoryRepository must implement paginateStockItems().'
        );
    }

    // -------------------------------------------------------------------------
    // InventoryController::listStockItems — method existence and signature
    // -------------------------------------------------------------------------

    public function test_inventory_controller_has_list_stock_items_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryController::class, 'listStockItems'),
            'InventoryController must expose a public listStockItems() method.'
        );
    }

    public function test_controller_list_stock_items_is_public(): void
    {
        $reflection = new \ReflectionMethod(InventoryController::class, 'listStockItems');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_controller_list_stock_items_returns_json_response(): void
    {
        $reflection = new \ReflectionMethod(InventoryController::class, 'listStockItems');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('Illuminate\Http\JsonResponse', $returnType);
    }
}
