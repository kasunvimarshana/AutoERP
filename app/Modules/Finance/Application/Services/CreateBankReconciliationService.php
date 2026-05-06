<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateBankReconciliationServiceInterface;
use Modules\Finance\Application\DTOs\BankReconciliationData;
use Modules\Finance\Domain\Entities\BankReconciliation;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;

class CreateBankReconciliationService extends BaseService implements CreateBankReconciliationServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $bankReconciliationRepository)
    {
        parent::__construct($bankReconciliationRepository);
    }

    protected function handle(array $data): BankReconciliation
    {
        $dto = BankReconciliationData::fromArray($data);

        $br = new BankReconciliation(
            tenantId: $dto->tenantId,
            bankAccountId: $dto->bankAccountId,
            periodStart: new \DateTimeImmutable($dto->periodStart),
            periodEnd: new \DateTimeImmutable($dto->periodEnd),
            openingBalance: $dto->openingBalance,
            closingBalance: $dto->closingBalance,
            status: $dto->status,
        );

        return $this->bankReconciliationRepository->save($br);
    }
}
