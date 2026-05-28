<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Application\Services\StockMovementService;
use PHPUnit\Framework\TestCase;

final class StockMovementServiceTest extends TestCase
{
    public function testItRejectsPostedMovementStructuralMutations(): void
    {
        $repository = $this->createMock(StockMovementRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(50)
            ->willReturn(new DataRecord([
                'id' => 50,
                'tenant_id' => 1,
                'status' => 'POSTED',
            ]));

        $repository->expects(self::never())->method('update');

        $service = new StockMovementService($repository);

        $result = $service->updateMovement(50, ['item_id' => 200]);

        self::assertTrue($result->isFailure());
    }
}
