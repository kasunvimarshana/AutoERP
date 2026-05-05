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
            tenantId: $dto->tenantId,
            supplierId: $dto->supplierId,
            accountId: $dto->accountId,
            transactionType: $dto->transactionType,
            amount: $dto->amount,
            balanceAfter: $dto->balanceAfter,
            transactionDate: new \DateTimeImmutable($dto->transactionDate),
            currencyId: $dto->currencyId,
            referenceType: $dto->referenceType,
            referenceId: $dto->referenceId,
            dueDate: $dto->dueDate !== null ? new \DateTimeImmutable($dto->dueDate) : null,
            isReconciled: $dto->isReconciled,
        );

        return $this->apTransactionRepository->save($ap);
    }
}
