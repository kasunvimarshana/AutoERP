<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\TraceLogRepositoryInterface;
use Modules\Inventory\Application\Services\TraceLogService;
use PHPUnit\Framework\TestCase;

final class TraceLogServiceTest extends TestCase
{
    public function testItCreatesValidatedTraceLog(): void
    {
        $repository = $this->createMock(TraceLogRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['entity_type'] ?? null) === 'item'
                    && ($payload['entity_id'] ?? null) === 100
                    && ($payload['action_type'] ?? null) === 'scan'
                    && ($payload['row_version'] ?? null) === 1;
            }))
            ->willReturn(new DataRecord(['id' => 60]));

        $service = new TraceLogService($repository);

        $result = $service->createTraceLog([
            'tenant_id' => 1,
            'entity_type' => 'item',
            'entity_id' => 100,
            'action_type' => 'scan',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsStructuralTraceLogUpdates(): void
    {
        $repository = $this->createMock(TraceLogRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(60)
            ->willReturn(new DataRecord([
                'id' => 60,
                'tenant_id' => 1,
                'entity_type' => 'item',
                'entity_id' => 100,
                'action_type' => 'scan',
            ]));

        $repository->expects(self::never())->method('update');

        $service = new TraceLogService($repository);

        $result = $service->updateTraceLog(60, ['entity_id' => 200]);

        self::assertTrue($result->isFailure());
    }
}
