<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Contracts\InventoryEngines\InventoryStrategyRegistryInterface;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Application\Services\ValuationConfigService;
use PHPUnit\Framework\TestCase;

final class ValuationConfigServiceTest extends TestCase
{
    public function test_it_creates_valuation_config_with_default_row_version(): void
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

        $strategyRegistry = $this->createMock(InventoryStrategyRegistryInterface::class);
        $strategyRegistry
            ->method('valuationMethods')
            ->willReturn(['fifo', 'weighted_average']);

        $service = new ValuationConfigService($repository, $strategyRegistry);

        $result = $service->createConfig([
            'tenant_id' => 1,
            'valuation_method' => 'fifo',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function test_it_rejects_unknown_valuation_method(): void
    {
        $repository = $this->createMock(ValuationConfigRepositoryInterface::class);

        $repository->expects(self::never())->method('create');

        $strategyRegistry = $this->createMock(InventoryStrategyRegistryInterface::class);
        $strategyRegistry
            ->method('valuationMethods')
            ->willReturn(['fifo', 'weighted_average']);

        $service = new ValuationConfigService($repository, $strategyRegistry);

        $result = $service->createConfig([
            'tenant_id' => 1,
            'valuation_method' => 'invalid-method',
        ]);

        self::assertTrue($result->isFailure());
    }
}
