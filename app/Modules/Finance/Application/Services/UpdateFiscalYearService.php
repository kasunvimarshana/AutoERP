<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateFiscalYearServiceInterface;
use Modules\Finance\Application\DTOs\FiscalYearData;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\Exceptions\FiscalYearAlreadyExistsException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Domain\Exceptions\FiscalYearNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;

class UpdateFiscalYearService extends BaseService implements UpdateFiscalYearServiceInterface
{
    public function __construct(private readonly FiscalYearRepositoryInterface $fiscalYearRepository)
    {
        parent::__construct($fiscalYearRepository);
    }

    protected function handle(array $data): FiscalYear
    {
        $id = (int) ($data['id'] ?? 0);
        $fiscalYear = $this->fiscalYearRepository->find($id);

        if (! $fiscalYear) {
            throw new FiscalYearNotFoundException($id);
        }

        $dto = FiscalYearData::fromArray($data);

        if ($dto->rowVersion !== $fiscalYear->getRowVersion()) {
            throw new ConcurrentModificationException('FiscalYear', $id);
        }

        $existing = $this->fiscalYearRepository->findByTenantAndName($dto->tenant_id, $dto->name);
        if ($existing !== null && $existing->getId() !== $fiscalYear->getId()) {
            throw new FiscalYearAlreadyExistsException($dto->tenant_id, $dto->name);
        }

        $fiscalYear->update(
            name: $dto->name,
            startDate: new \DateTimeImmutable($dto->start_date),
            endDate: new \DateTimeImmutable($dto->end_date),
            status: $dto->status,
        );

        return $this->fiscalYearRepository->save($fiscalYear);
    }
}
