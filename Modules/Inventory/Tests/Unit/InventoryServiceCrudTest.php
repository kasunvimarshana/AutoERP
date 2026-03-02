<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use Modules\Inventory\Infrastructure\Repositories\InventoryRepository;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for InventoryService releaseReservation and listTransactions methods,
 * and for deleteReservation / paginateTransactions on the repository.
 */
class InventoryServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // releaseReservation — method existence and signature
    // -------------------------------------------------------------------------

    public function test_inventory_service_has_release_reservation_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'releaseReservation'),
            'InventoryService must expose a public releaseReservation() method.'
        );
    }

    public function test_release_reservation_is_public(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'releaseReservation');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_release_reservation_accepts_reservation_id_param(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'releaseReservation');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('reservationId', $params[0]->getName());
    }

    public function test_release_reservation_has_bool_return_type(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'releaseReservation');

        $this->assertSame('bool', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // listTransactions — method existence and signature
    // -------------------------------------------------------------------------

    public function test_inventory_service_has_list_transactions_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'listTransactions'),
            'InventoryService must expose a public listTransactions() method.'
        );
    }

    public function test_list_transactions_is_public(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'listTransactions');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_transactions_has_optional_per_page_param_with_default_15(): void
    {
        $reflection = new \ReflectionMethod(InventoryService::class, 'listTransactions');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('perPage', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertSame(15, $params[1]->getDefaultValue());
    }

    // -------------------------------------------------------------------------
    // Repository contract methods
    // -------------------------------------------------------------------------

    public function test_inventory_repository_contract_has_delete_reservation_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryRepositoryContract::class, 'deleteReservation'),
            'InventoryRepositoryContract must declare deleteReservation().'
        );
    }

    public function test_inventory_repository_contract_has_paginate_transactions_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryRepositoryContract::class, 'paginateTransactions'),
            'InventoryRepositoryContract must declare paginateTransactions().'
        );
    }

    public function test_inventory_repository_implements_delete_reservation(): void
    {
        $this->assertTrue(
            method_exists(InventoryRepository::class, 'deleteReservation'),
            'InventoryRepository must implement deleteReservation().'
        );
    }

    public function test_inventory_repository_implements_paginate_transactions(): void
    {
        $this->assertTrue(
            method_exists(InventoryRepository::class, 'paginateTransactions'),
            'InventoryRepository must implement paginateTransactions().'
        );
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_inventory_service_can_be_instantiated(): void
    {
        $repo    = $this->createMock(InventoryRepositoryContract::class);
        $service = new InventoryService($repo);

        $this->assertInstanceOf(InventoryService::class, $service);
    }
}
