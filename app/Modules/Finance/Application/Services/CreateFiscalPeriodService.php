<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\DTOs\FiscalPeriodData;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\Exceptions\FiscalPeriodAlreadyExistsException;
use Modules\Finance\Domain\Exceptions\FiscalYearNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;

class CreateFiscalPeriodService extends BaseService implements CreateFiscalPeriodServiceInterface
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly FiscalYearRepositoryInterface $fiscalYearRepository,
    ) {
        parent::__construct($fiscalPeriodRepository);
    }

    protected function handle(array $data): FiscalPeriod
    {
        $dto = FiscalPeriodData::fromArray($data);

        $fiscalYear = $this->fiscalYearRepository->find($dto->fiscal_year_id);
        if (! $fiscalYear) {
            throw new FiscalYearNotFoundException($dto->fiscal_year_id);
        }

        $existing = $this->fiscalPeriodRepository->findByTenantAndYearAndPeriodNumber(
            $dto->tenant_id,
            $dto->fiscal_year_id,
            $dto->period_number,
        );
        if ($existing !== null) {
            throw new FiscalPeriodAlreadyExistsException(
                $dto->tenant_id,
                $dto->fiscal_year_id,
                $dto->period_number,
            );
        }

        $fiscalPeriod = new FiscalPeriod(
            tenantId: $dto->tenant_id,
            fiscalYearId: $dto->fiscal_year_id,
            periodNumber: $dto->period_number,
            name: $dto->name,
            startDate: new \DateTimeImmutable($dto->start_date),
            endDate: new \DateTimeImmutable($dto->end_date),
            status: $dto->status,
        );

        return $this->fiscalPeriodRepository->save($fiscalPeriod);
    }
}
