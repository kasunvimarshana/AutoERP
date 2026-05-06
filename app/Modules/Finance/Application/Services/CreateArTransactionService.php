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
            tenantId: $dto->tenantId,
            customerId: $dto->customerId,
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

        return $this->arTransactionRepository->save($ar);
    }
}
