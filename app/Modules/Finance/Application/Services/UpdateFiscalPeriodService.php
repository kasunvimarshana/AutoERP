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

        $fiscalYear = $this->fiscalYearRepository->find($dto->fiscalYearId);
        if (! $fiscalYear) {
            throw new FiscalYearNotFoundException($dto->fiscalYearId);
        }

        $existing = $this->fiscalPeriodRepository->findByTenantAndYearAndPeriodNumber(
            $dto->tenantId,
            $dto->fiscalYearId,
            $dto->periodNumber,
        );
        if ($existing !== null && $existing->getId() !== $fiscalPeriod->getId()) {
            throw new FiscalPeriodAlreadyExistsException(
                $dto->tenantId,
                $dto->fiscalYearId,
                $dto->periodNumber,
            );
        }

        $fiscalPeriod->update(
            fiscalYearId: $dto->fiscalYearId,
            periodNumber: $dto->periodNumber,
            name: $dto->name,
            startDate: new \DateTimeImmutable($dto->startDate),
            endDate: new \DateTimeImmutable($dto->endDate),
            status: $dto->status,
        );

        return $this->fiscalPeriodRepository->save($fiscalPeriod);
    }
}
