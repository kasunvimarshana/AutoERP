<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\TransferOrderLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Application\Services\TransferOrderLineService;
use Modules\Inventory\Application\Services\TransferOrderService;
use PHPUnit\Framework\TestCase;

final class TransferOrderServiceTest extends TestCase
{
    public function testItCreatesValidatedTransferOrderWithDefaults(): void
    {
        $repository = $this->createMock(TransferOrderRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['from_warehouse_id'] ?? null) === 10
                    && ($payload['to_warehouse_id'] ?? null) === 20
                    && ($payload['transfer_number'] ?? null) === 'TO-001'
                    && ($payload['status'] ?? null) === 'DRAFT'
                    && ($payload['row_version'] ?? null) === 1;
            }))
            ->willReturn(new DataRecord(['id' => 100]));

        $service = new TransferOrderService($repository);

        $result = $service->createOrder([
            'tenant_id' => 1,
            'from_warehouse_id' => 10,
            'to_warehouse_id' => 20,
            'transfer_number' => 'TO-001',
            'request_date' => '2026-05-28',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsTransferOrderLineWithInvalidQuantities(): void
    {
        $orderRepository = $this->createMock(TransferOrderRepositoryInterface::class);
        $lineRepository = $this->createMock(TransferOrderLineRepositoryInterface::class);

        $orderRepository
            ->method('findById')
            ->with(100)
            ->willReturn(new DataRecord([
                'id' => 100,
                'tenant_id' => 1,
            ]));

        $lineRepository->expects(self::never())->method('create');

        $service = new TransferOrderLineService(
            $lineRepository,
            $orderRepository,
        );

        $result = $service->createLine([
            'tenant_id' => 1,
            'transfer_order_id' => 100,
            'item_id' => 200,
            'uom_id' => 1,
            'requested_qty' => 10,
            'shipped_qty' => 12,
        ]);

        self::assertTrue($result->isFailure());
    }
}
