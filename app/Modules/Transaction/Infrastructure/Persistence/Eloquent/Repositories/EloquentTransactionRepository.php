<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\TransactionModel;

class EloquentTransactionRepository extends EloquentRepository implements TransactionRepositoryInterface
{
    public function __construct(TransactionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TransactionModel $m): Transaction => $this->mapModelToDomainEntity($m));
    }

    public function save(Transaction $transaction): Transaction
    {
        $savedModel = null;
        DB::transaction(function () use ($transaction, &$savedModel) {
            $data = [
                'tenant_id'        => $transaction->getTenantId(),
                'reference_number' => $transaction->getReferenceNumber(),
                'transaction_type' => $transaction->getTransactionType(),
                'status'           => $transaction->getStatus(),
                'amount'           => $transaction->getAmount(),
                'currency_code'    => $transaction->getCurrencyCode(),
                'exchange_rate'    => $transaction->getExchangeRate(),
                'transaction_date' => $transaction->getTransactionDate()->format('Y-m-d'),
                'description'      => $transaction->getDescription(),
                'reference_type'   => $transaction->getReferenceType(),
                'reference_id'     => $transaction->getReferenceId(),
                'posted_at'        => $transaction->getPostedAt()?->format('Y-m-d H:i:s'),
                'voided_at'        => $transaction->getVoidedAt()?->format('Y-m-d H:i:s'),
                'void_reason'      => $transaction->getVoidReason(),
                'metadata'         => $transaction->getMetadata()->toArray(),
            ];
            if ($transaction->getId()) {
                $savedModel = $this->update($transaction->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof TransactionModel) {
            throw new \RuntimeException('Failed to save Transaction.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?Transaction
    {
        $model = $this->model->newQuery()->find($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByReference(int $tenantId, string $referenceNumber): ?Transaction
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('reference_number', $referenceNumber)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByReferenceEntity(int $tenantId, string $referenceType, int $referenceId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByType(int $tenantId, string $transactionType): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('transaction_type', $transactionType)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function list(array $filters = [], int $perPage = 15, int $page = 1): mixed
    {
        return $this->model->newQuery()
            ->when(isset($filters['tenant_id']), fn ($q) => $q->where('tenant_id', $filters['tenant_id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['transaction_type']), fn ($q) => $q->where('transaction_type', $filters['transaction_type']))
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function mapModelToDomainEntity(TransactionModel $model): Transaction
    {
        return new Transaction(
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            transactionType: $model->transaction_type,
            amount:          (float) $model->amount,
            transactionDate: new \DateTimeImmutable($model->transaction_date),
            status:          $model->status,
            currencyCode:    $model->currency_code,
            exchangeRate:    (float) $model->exchange_rate,
            description:     $model->description,
            referenceType:   $model->reference_type,
            referenceId:     $model->reference_id,
            postedAt:        $model->posted_at ? new \DateTimeImmutable($model->posted_at->format('c')) : null,
            voidedAt:        $model->voided_at ? new \DateTimeImmutable($model->voided_at->format('c')) : null,
            voidReason:      $model->void_reason,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
