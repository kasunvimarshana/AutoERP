<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Transaction\Application\Contracts\UpdateTransactionServiceInterface;
use Modules\Transaction\Application\DTOs\UpdateTransactionData;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Exceptions\TransactionNotFoundException;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class UpdateTransactionService extends BaseService implements UpdateTransactionServiceInterface
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository)
    {
        parent::__construct($transactionRepository);
    }

    protected function handle(array $data): Transaction
    {
        $dto = UpdateTransactionData::fromArray($data);

        /** @var Transaction|null $transaction */
        $transaction = $this->transactionRepository->find($dto->id);
        if (! $transaction) {
            throw new TransactionNotFoundException($dto->id);
        }

        $transaction->updateDetails(
            transactionType: $dto->transactionType ?? $transaction->getTransactionType(),
            amount:          $dto->amount ?? $transaction->getAmount(),
            transactionDate: $dto->transactionDate
                ? new \DateTimeImmutable($dto->transactionDate)
                : $transaction->getTransactionDate(),
            currencyCode:    $dto->currencyCode ?? $transaction->getCurrencyCode(),
            exchangeRate:    $dto->exchangeRate ?? $transaction->getExchangeRate(),
            description:     $dto->description ?? $transaction->getDescription(),
            referenceType:   $dto->referenceType ?? $transaction->getReferenceType(),
            referenceId:     $dto->referenceId ?? $transaction->getReferenceId(),
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        return $this->transactionRepository->save($transaction);
    }
}
