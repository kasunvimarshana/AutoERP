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
            tenantId: $dto->tenant_id,
            bankAccountId: $dto->bank_account_id,
            periodStart: new \DateTimeImmutable($dto->period_start),
            periodEnd: new \DateTimeImmutable($dto->period_end),
            openingBalance: $dto->opening_balance,
            closingBalance: $dto->closing_balance,
            status: $dto->status,
        );

        return $this->bankReconciliationRepository->save($br);
    }
}
