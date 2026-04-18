<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use Modules\Finance\Application\Services\CreateFiscalYearService;
use Modules\Finance\Application\Services\UpdateFiscalYearService;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\Exceptions\FiscalYearAlreadyExistsException;
use Modules\Finance\Domain\Exceptions\FiscalYearNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FiscalYearServiceTest extends TestCase
{
    /** @var FiscalYearRepositoryInterface&MockObject */
    private FiscalYearRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(FiscalYearRepositoryInterface::class);
    }

    public function test_update_fiscal_year_service_throws_when_year_missing(): void
    {
        $service = new UpdateFiscalYearService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(FiscalYearNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 1,
            'name' => 'FY2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
    }

    public function test_create_fiscal_year_service_throws_when_name_already_exists_for_tenant(): void
    {
        $service = new CreateFiscalYearService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('findByTenantAndName')
            ->with(1, 'FY2026')
            ->willReturn(new FiscalYear(
                tenantId: 1,
                name: 'FY2026',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-12-31'),
                status: 'open',
                id: 10,
            ));

        $this->repository
            ->expects($this->never())
            ->method('save');

        $this->expectException(FiscalYearAlreadyExistsException::class);

        $service->execute([
            'tenant_id' => 1,
            'name' => 'FY2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
    }

    public function test_update_fiscal_year_service_throws_when_name_conflicts_with_another_record(): void
    {
        $service = new UpdateFiscalYearService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(11)
            ->willReturn(new FiscalYear(
                tenantId: 1,
                name: 'FY2027',
                startDate: new \DateTimeImmutable('2027-01-01'),
                endDate: new \DateTimeImmutable('2027-12-31'),
                status: 'open',
                id: 11,
            ));

        $this->repository
            ->expects($this->once())
            ->method('findByTenantAndName')
            ->with(1, 'FY2026')
            ->willReturn(new FiscalYear(
                tenantId: 1,
                name: 'FY2026',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-12-31'),
                status: 'open',
                id: 10,
            ));

        $this->repository
            ->expects($this->never())
            ->method('save');

        $this->expectException(FiscalYearAlreadyExistsException::class);

        $service->execute([
            'id' => 11,
            'tenant_id' => 1,
            'name' => 'FY2026',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'open',
        ]);
    }
}
