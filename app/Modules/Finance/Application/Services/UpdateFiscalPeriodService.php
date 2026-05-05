<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\DTOs\FiscalPeriodData;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\Exceptions\FiscalPeriodAlreadyExistsException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Domain\Exceptions\FiscalPeriodNotFoundException;
use Modules\Finance\Domain\Exceptions\FiscalYearNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;

class UpdateFiscalPeriodService extends BaseService implements UpdateFiscalPeriodServiceInterface
{
    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly FiscalYearRepositoryInterface $fiscalYearRepository,
    ) {
        parent::__construct($fiscalPeriodRepository);
    }

    protected function handle(array $data): FiscalPeriod
    {
        $id = (int) ($data['id'] ?? 0);
        $fiscalPeriod = $this->fiscalPeriodRepository->find($id);

        if (! $fiscalPeriod) {
            throw FiscalPeriodNotFoundException::byId($id);
        }

        $dto = FiscalPeriodData::fromArray($data);


        if ($dto->rowVersion !== $fiscalPeriod->getRowVersion()) {
            throw new ConcurrentModificationException('FiscalPeriod', $id);
        }

        $fiscalYear = $this->fiscalYearRepository->find($dto->fiscal_year_id);
        if (! $fiscalYear) {
            throw new FiscalYearNotFoundException($dto->fiscal_year_id);
        }

        $existing = $this->fiscalPeriodRepository->findByTenantAndYearAndPeriodNumber(
            $dto->tenant_id,
            $dto->fiscal_year_id,
            $dto->period_number,
        );
        if ($existing !== null && $existing->getId() !== $fiscalPeriod->getId()) {
            throw new FiscalPeriodAlreadyExistsException(
                $dto->tenant_id,
                $dto->fiscal_year_id,
                $dto->period_number,
            );
        }

        $fiscalPeriod->update(
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
