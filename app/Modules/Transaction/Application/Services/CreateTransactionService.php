<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Transaction\Application\Contracts\CreateTransactionServiceInterface;
use Modules\Transaction\Application\DTOs\TransactionData;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Events\TransactionCreated;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class CreateTransactionService extends BaseService implements CreateTransactionServiceInterface
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository)
    {
        parent::__construct($transactionRepository);
    }

    protected function handle(array $data): Transaction
    {
        $dto = TransactionData::fromArray($data);

        $transaction = new Transaction(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber,
            transactionType: $dto->transactionType,
            amount:          $dto->amount,
            transactionDate: new \DateTimeImmutable($dto->transactionDate),
            status:          $dto->status,
            currencyCode:    $dto->currencyCode,
            exchangeRate:    $dto->exchangeRate,
            description:     $dto->description,
            referenceType:   $dto->referenceType,
            referenceId:     $dto->referenceId,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->transactionRepository->save($transaction);
        $this->addEvent(new TransactionCreated($saved));

        return $saved;
    }
}
