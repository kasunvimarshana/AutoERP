<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Application\Services\SerialService;
use PHPUnit\Framework\TestCase;

final class SerialServiceTest extends TestCase
{
    public function testItCreatesSerialWithDefaultStatusAndRowVersion(): void
    {
        $repository = $this->createMock(SerialRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['serial_number'] ?? null) === 'SN-001'
                    && ($payload['status'] ?? null) === 'AVAILABLE'
                    && ($payload['row_version'] ?? null) === 1;
            }))
            ->willReturn(new DataRecord(['id' => 20]));

        $service = new SerialService($repository);

        $result = $service->createSerial([
            'tenant_id' => 1,
            'item_id' => 100,
            'serial_number' => 'SN-001',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsLockedSerialStructuralMutations(): void
    {
        $repository = $this->createMock(SerialRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(20)
            ->willReturn(new DataRecord([
                'id' => 20,
                'tenant_id' => 1,
                'item_id' => 100,
                'serial_number' => 'SN-001',
                'status' => 'SCRAPPED',
            ]));

        $repository->expects(self::never())->method('update');

        $service = new SerialService($repository);

        $result = $service->updateSerial(20, ['serial_number' => 'SN-002']);

        self::assertTrue($result->isFailure());
    }
}
