<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Services\CreateUnitOfMeasureService;
use Modules\Product\Application\Services\DeleteUnitOfMeasureService;
use Modules\Product\Application\Services\FindUnitOfMeasureService;
use Modules\Product\Application\Services\UpdateUnitOfMeasureService;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Product\Domain\Exceptions\UnitOfMeasureNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UnitOfMeasureServiceTest extends TestCase
{
    /** @var UnitOfMeasureRepositoryInterface&MockObject */
    private UnitOfMeasureRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(UnitOfMeasureRepositoryInterface::class);
    }

    public function test_create_unit_of_measure_service_maps_payload_and_saves(): void
    {
        $service = new CreateUnitOfMeasureService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $unitOfMeasure): bool {
                if (! $unitOfMeasure instanceof UnitOfMeasure) {
                    return false;
                }

                return $unitOfMeasure->getTenantId() === 7
                    && $unitOfMeasure->getName() === 'Each'
                    && $unitOfMeasure->getSymbol() === 'EA';
            }))
            ->willReturn($this->buildUnit(400));

        $result = $service->execute([
            'tenant_id' => 7,
            'name' => 'Each',
            'symbol' => 'EA',
            'type' => 'unit',
            'is_base' => true,
        ]);

        $this->assertInstanceOf(UnitOfMeasure::class, $result);
        $this->assertSame(400, $result->getId());
    }

    public function test_find_unit_of_measure_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindUnitOfMeasureService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('name', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 7,
                'name' => 'ea',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-name',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_unit_of_measure_service_throws_when_unit_missing(): void
    {
        $service = new UpdateUnitOfMeasureService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(UnitOfMeasureNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 7,
            'name' => 'Missing',
            'symbol' => 'MS',
        ]);
    }

    public function test_delete_unit_of_measure_service_throws_when_unit_missing(): void
    {
        $service = new DeleteUnitOfMeasureService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(UnitOfMeasureNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildUnit(int $id): UnitOfMeasure
    {
        return new UnitOfMeasure(
            id: $id,
            tenantId: 7,
            name: 'Each',
            symbol: 'EA',
            type: 'unit',
            isBase: true,
        );
    }
}
