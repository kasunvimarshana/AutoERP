<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Application\Services\ValuationConfigService;
use PHPUnit\Framework\TestCase;

final class ValuationConfigServiceTest extends TestCase
{
    public function testItCreatesValuationConfigWithDefaultRowVersion(): void
    {
        $repository = $this->createMock(ValuationConfigRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['valuation_method'] ?? null) === 'fifo'
                    && ($payload['row_version'] ?? null) === 1;
            }))
            ->willReturn(new DataRecord(['id' => 50]));

        $service = new ValuationConfigService($repository);

        $result = $service->createConfig([
            'tenant_id' => 1,
            'valuation_method' => 'fifo',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsUnknownValuationMethod(): void
    {
        $repository = $this->createMock(ValuationConfigRepositoryInterface::class);

        $repository->expects(self::never())->method('create');

        $service = new ValuationConfigService($repository);

        $result = $service->createConfig([
            'tenant_id' => 1,
            'valuation_method' => 'invalid-method',
        ]);

        self::assertTrue($result->isFailure());
    }
}
