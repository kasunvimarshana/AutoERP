<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Repositories\ReceiptInspectionRepositoryInterface;
use Modules\Inventory\Application\Services\ReceiptInspectionService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class ReceiptInspectionServiceTest extends TestCase
{
    public function testItCreatesValidatedReceiptInspectionWithNormalizedBaseQuantity(): void
    {
        $receiptInspectionRepository = $this->createMock(ReceiptInspectionRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);

        $itemRepository
            ->method('findByIdInTenant')
            ->with(100, 1)
            ->willReturn(new DataRecord([
                'id' => 100,
                'tenant_id' => 1,
                'base_uom_id' => 1,
                'is_stockable' => true,
            ]));

        $uomConversionService
            ->method('convert')
            ->willReturn(Result::success(12.0));

        $receiptInspectionRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['transaction_uom_id'] ?? null) === 2
                    && ($payload['base_uom_id'] ?? null) === 1
                    && (float) ($payload['received_quantity'] ?? 0) === 6.0
                    && (float) ($payload['base_received_quantity'] ?? 0) === 12.0
                    && (float) ($payload['accepted_quantity'] ?? 0) === 4.0
                    && (float) ($payload['rejected_quantity'] ?? 0) === 1.0
                    && (float) ($payload['damaged_quantity'] ?? 0) === 1.0
                    && ($payload['inspection_status'] ?? null) === 'PENDING';
            }))
            ->willReturn(new DataRecord(['id' => 1000]));

        $service = new ReceiptInspectionService(
            $receiptInspectionRepository,
            $itemRepository,
            $uomConversionService,
        );

        $result = $service->createInspection([
            'tenant_id' => 1,
            'item_id' => 100,
            'transaction_uom_id' => 2,
            'received_quantity' => 6,
            'accepted_quantity' => 4,
            'rejected_quantity' => 1,
            'damaged_quantity' => 1,
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }
}
