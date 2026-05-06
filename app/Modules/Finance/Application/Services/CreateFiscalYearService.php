<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\DTOs\FiscalYearData;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\Exceptions\FiscalYearAlreadyExistsException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;

class CreateFiscalYearService extends BaseService implements CreateFiscalYearServiceInterface
{
    public function __construct(private readonly FiscalYearRepositoryInterface $fiscalYearRepository)
    {
        parent::__construct($fiscalYearRepository);
    }

    protected function handle(array $data): FiscalYear
    {
        $dto = FiscalYearData::fromArray($data);

        $existing = $this->fiscalYearRepository->findByTenantAndName($dto->tenant_id, $dto->name);
        if ($existing !== null) {
            throw new FiscalYearAlreadyExistsException($dto->tenant_id, $dto->name);
        }

        $fiscalYear = new FiscalYear(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            startDate: new \DateTimeImmutable($dto->start_date),
            endDate: new \DateTimeImmutable($dto->end_date),
            status: $dto->status,
        );

        return $this->fiscalYearRepository->save($fiscalYear);
    }
}
