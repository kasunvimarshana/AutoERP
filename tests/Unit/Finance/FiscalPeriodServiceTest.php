<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use Modules\Finance\Application\Services\CreateFiscalPeriodService;
use Modules\Finance\Application\Services\UpdateFiscalPeriodService;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\Exceptions\FiscalPeriodAlreadyExistsException;
use Modules\Finance\Domain\Exceptions\FiscalYearNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FiscalPeriodServiceTest extends TestCase
{
    /** @var FiscalPeriodRepositoryInterface&MockObject */
    private FiscalPeriodRepositoryInterface $fiscalPeriodRepository;

    /** @var FiscalYearRepositoryInterface&MockObject */
    private FiscalYearRepositoryInterface $fiscalYearRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fiscalPeriodRepository = $this->createMock(FiscalPeriodRepositoryInterface::class);
        $this->fiscalYearRepository = $this->createMock(FiscalYearRepositoryInterface::class);
    }

    public function test_create_fiscal_period_throws_when_fiscal_year_missing(): void
    {
        $service = new CreateFiscalPeriodService($this->fiscalPeriodRepository, $this->fiscalYearRepository);

        $this->fiscalYearRepository
            ->expects($this->once())
            ->method('find')
            ->with(77)
            ->willReturn(null);

        $this->fiscalPeriodRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(FiscalYearNotFoundException::class);

        $service->execute([
            'tenant_id' => 1,
            'fiscal_year_id' => 77,
            'period_number' => 1,
            'name' => 'P1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
        ]);
    }

    public function test_create_fiscal_period_throws_when_period_number_already_exists(): void
    {
        $service = new CreateFiscalPeriodService($this->fiscalPeriodRepository, $this->fiscalYearRepository);

        $this->fiscalYearRepository
            ->expects($this->once())
            ->method('find')
            ->with(77)
            ->willReturn(new FiscalYear(
                tenantId: 1,
                name: 'FY2026',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-12-31'),
                status: 'open',
                id: 77,
            ));

        $this->fiscalPeriodRepository
            ->expects($this->once())
            ->method('findByTenantAndYearAndPeriodNumber')
            ->with(1, 77, 1)
            ->willReturn(new FiscalPeriod(
                tenantId: 1,
                fiscalYearId: 77,
                periodNumber: 1,
                name: 'P1',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-01-31'),
                status: 'open',
                id: 99,
            ));

        $this->fiscalPeriodRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(FiscalPeriodAlreadyExistsException::class);

        $service->execute([
            'tenant_id' => 1,
            'fiscal_year_id' => 77,
            'period_number' => 1,
            'name' => 'P1 Duplicate',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
        ]);
    }

    public function test_update_fiscal_period_throws_when_period_number_conflicts_with_another_record(): void
    {
        $service = new UpdateFiscalPeriodService($this->fiscalPeriodRepository, $this->fiscalYearRepository);

        $this->fiscalPeriodRepository
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn(new FiscalPeriod(
                tenantId: 1,
                fiscalYearId: 77,
                periodNumber: 2,
                name: 'P2',
                startDate: new \DateTimeImmutable('2026-02-01'),
                endDate: new \DateTimeImmutable('2026-02-28'),
                status: 'open',
                id: 100,
            ));

        $this->fiscalYearRepository
            ->expects($this->once())
            ->method('find')
            ->with(77)
            ->willReturn(new FiscalYear(
                tenantId: 1,
                name: 'FY2026',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-12-31'),
                status: 'open',
                id: 77,
            ));

        $this->fiscalPeriodRepository
            ->expects($this->once())
            ->method('findByTenantAndYearAndPeriodNumber')
            ->with(1, 77, 1)
            ->willReturn(new FiscalPeriod(
                tenantId: 1,
                fiscalYearId: 77,
                periodNumber: 1,
                name: 'P1',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-01-31'),
                status: 'open',
                id: 99,
            ));

        $this->fiscalPeriodRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(FiscalPeriodAlreadyExistsException::class);

        $service->execute([
            'id' => 100,
            'tenant_id' => 1,
            'fiscal_year_id' => 77,
            'period_number' => 1,
            'name' => 'P1 Duplicate',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
        ]);
    }
}
