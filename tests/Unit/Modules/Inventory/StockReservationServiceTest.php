<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Application\Services\StockReservationService;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class StockReservationServiceTest extends TestCase
{
    public function testItRejectsReservationWhenAvailableStockIsInsufficient(): void
    {
        $reservationRepository = $this->createMock(StockReservationRepositoryInterface::class);
        $stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $batchRepository = $this->createMock(BatchRepositoryInterface::class);
        $serialRepository = $this->createMock(SerialRepositoryInterface::class);

        $itemRepository
            ->method('findByIdInTenant')
            ->with(100, 1)
            ->willReturn(new DataRecord([
                'id' => 100,
                'tenant_id' => 1,
                'base_uom_id' => 1,
                'is_stockable' => true,
                'is_serial_tracked' => false,
            ]));

        $uomConversionService
            ->method('convert')
            ->with(5.0, 2, 1, 1, 100)
            ->willReturn(Result::success(5.0));

        $stockLevelRepository
            ->method('list')
            ->willReturn([
                new DataRecord([
                    'id' => 42,
                    'quantity_on_hand' => 4.0,
                    'quantity_reserved' => 1.0,
                    'quantity_blocked' => 0.0,
                ]),
            ]);

        $service = new StockReservationService(
            $reservationRepository,
            $stockLevelRepository,
            $itemRepository,
            $uomConversionService,
            $batchRepository,
            $serialRepository,
        );

        $result = $service->reserve([
            'tenant_id' => 1,
            'item_id' => 100,
            'warehouse_id' => 10,
            'uom_id' => 2,
            'quantity' => 5,
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame(InventoryErrorCode::INSUFFICIENT_STOCK, $result->errorOrFail()->code);
    }

    public function testItRejectsStructuralReservationUpdateFields(): void
    {
        $reservationRepository = $this->createMock(StockReservationRepositoryInterface::class);
        $stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $batchRepository = $this->createMock(BatchRepositoryInterface::class);
        $serialRepository = $this->createMock(SerialRepositoryInterface::class);

        $reservationRepository
            ->method('findById')
            ->with(77)
            ->willReturn(new DataRecord([
                'id' => 77,
                'status' => 'ACTIVE',
            ]));

        $reservationRepository->expects(self::never())->method('update');

        $service = new StockReservationService(
            $reservationRepository,
            $stockLevelRepository,
            $itemRepository,
            $uomConversionService,
            $batchRepository,
            $serialRepository,
        );

        $result = $service->updateReservation(77, ['quantity' => 10]);

        self::assertTrue($result->isFailure());
        self::assertSame(InventoryErrorCode::INVALID_VALUE, $result->errorOrFail()->code);
    }

    public function testItAllowsMutableReservationFieldsForActiveReservation(): void
    {
        $reservationRepository = $this->createMock(StockReservationRepositoryInterface::class);
        $stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $batchRepository = $this->createMock(BatchRepositoryInterface::class);
        $serialRepository = $this->createMock(SerialRepositoryInterface::class);

        $reservationRepository
            ->method('findById')
            ->with(77)
            ->willReturn(new DataRecord([
                'id' => 77,
                'status' => 'ACTIVE',
            ]));

        $reservationRepository
            ->expects(self::once())
            ->method('update')
            ->with(77, [
                'expires_at' => '2026-12-31 23:59:59',
                'notes' => 'hold for fulfillment',
            ])
            ->willReturn(new DataRecord([
                'id' => 77,
                'status' => 'ACTIVE',
                'expires_at' => '2026-12-31 23:59:59',
                'notes' => 'hold for fulfillment',
            ]));

        $service = new StockReservationService(
            $reservationRepository,
            $stockLevelRepository,
            $itemRepository,
            $uomConversionService,
            $batchRepository,
            $serialRepository,
        );

        $result = $service->updateReservation(77, [
            'expires_at' => '2026-12-31 23:59:59',
            'notes' => 'hold for fulfillment',
        ]);

        self::assertTrue($result->isSuccess());
        self::assertSame(77, $result->valueOrFail()->id());
    }

    public function testItRejectsNonNoteUpdatesForClosedReservation(): void
    {
        $reservationRepository = $this->createMock(StockReservationRepositoryInterface::class);
        $stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $batchRepository = $this->createMock(BatchRepositoryInterface::class);
        $serialRepository = $this->createMock(SerialRepositoryInterface::class);

        $reservationRepository
            ->method('findById')
            ->with(77)
            ->willReturn(new DataRecord([
                'id' => 77,
                'status' => 'CONSUMED',
            ]));

        $reservationRepository->expects(self::never())->method('update');

        $service = new StockReservationService(
            $reservationRepository,
            $stockLevelRepository,
            $itemRepository,
            $uomConversionService,
            $batchRepository,
            $serialRepository,
        );

        $result = $service->updateReservation(77, ['expires_at' => '2026-12-31 23:59:59']);

        self::assertTrue($result->isFailure());
        self::assertSame(InventoryErrorCode::INVALID_RESERVATION_STATUS, $result->errorOrFail()->code);
    }
}
