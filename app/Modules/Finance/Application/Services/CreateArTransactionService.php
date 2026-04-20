<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateArTransactionServiceInterface;
use Modules\Finance\Application\DTOs\ArTransactionData;
use Modules\Finance\Domain\Entities\ArTransaction;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;

class CreateArTransactionService extends BaseService implements CreateArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $arTransactionRepository)
    {
        parent::__construct($arTransactionRepository);
    }

    protected function handle(array $data): ArTransaction
    {
        $dto = ArTransactionData::fromArray($data);

        $ar = new ArTransaction(
            tenantId: $dto->tenant_id,
            customerId: $dto->customer_id,
            accountId: $dto->account_id,
            transactionType: $dto->transaction_type,
            amount: $dto->amount,
            balanceAfter: $dto->balance_after,
            transactionDate: new \DateTimeImmutable($dto->transaction_date),
            currencyId: $dto->currency_id,
            referenceType: $dto->reference_type,
            referenceId: $dto->reference_id,
            dueDate: $dto->due_date !== null ? new \DateTimeImmutable($dto->due_date) : null,
            isReconciled: $dto->is_reconciled,
        );

        return $this->arTransactionRepository->save($ar);
    }
}
