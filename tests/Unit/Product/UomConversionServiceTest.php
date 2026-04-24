<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Services\CreateUomConversionService;
use Modules\Product\Application\Services\DeleteUomConversionService;
use Modules\Product\Application\Services\FindUomConversionService;
use Modules\Product\Application\Services\UpdateUomConversionService;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Domain\Exceptions\UomConversionNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UomConversionServiceTest extends TestCase
{
    /** @var UomConversionRepositoryInterface&MockObject */
    private UomConversionRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(UomConversionRepositoryInterface::class);
    }

    public function test_create_uom_conversion_service_maps_payload_and_saves(): void
    {
        $service = new CreateUomConversionService($this->repository);

        $this->repository
            ->expects($this->exactly(2))
            ->method('findByUomPair')
            ->withAnyParameters()
            ->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $uomConversion): bool {
                if (! $uomConversion instanceof UomConversion) {
                    return false;
                }

                return $uomConversion->getFromUomId() === 11
                    && $uomConversion->getToUomId() === 12
                    && $uomConversion->getFactor() === '0.001';
            }))
            ->willReturn($this->buildUomConversion(900));

        $result = $service->execute([
            'from_uom_id' => 11,
            'to_uom_id' => 12,
            'factor' => '0.001',
        ]);

        $this->assertInstanceOf(UomConversion::class, $result);
        $this->assertSame(900, $result->getId());
    }

    public function test_find_uom_conversion_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindUomConversionService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('factor', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'from_uom_id' => 11,
                'to_uom_id' => 12,
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-factor',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_uom_conversion_service_throws_when_conversion_missing(): void
    {
        $service = new UpdateUomConversionService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(UomConversionNotFoundException::class);

        $service->execute([
            'id' => 999,
            'from_uom_id' => 11,
            'to_uom_id' => 12,
            'factor' => '2.0',
        ]);
    }

    public function test_delete_uom_conversion_service_throws_when_conversion_missing(): void
    {
        $service = new DeleteUomConversionService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(UomConversionNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildUomConversion(int $id): UomConversion
    {
        return new UomConversion(
            id: $id,
            fromUomId: 11,
            toUomId: 12,
            factor: '0.001',
        );
    }
}
