<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\DTOs\ApTransactionData;
use Modules\Finance\Domain\Entities\ApTransaction;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;

class CreateApTransactionService extends BaseService implements CreateApTransactionServiceInterface
{
    public function __construct(private readonly ApTransactionRepositoryInterface $apTransactionRepository)
    {
        parent::__construct($apTransactionRepository);
    }

    protected function handle(array $data): ApTransaction
    {
        $dto = ApTransactionData::fromArray($data);

        $ap = new ApTransaction(
            tenantId: $dto->tenant_id,
            supplierId: $dto->supplier_id,
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

        return $this->apTransactionRepository->save($ap);
    }
}
