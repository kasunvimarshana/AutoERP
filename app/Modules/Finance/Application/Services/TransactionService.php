<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Application\Contracts\TransactionServiceInterface;
use Modules\Finance\Application\DTOs\TransactionData;
use Modules\Finance\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

final class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $repository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function create(TransactionData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $referenceNumber = $this->repository->nextReferenceNumber($tenantId);

            $payload = array_filter($dto->toArray(), static fn ($v) => $v !== null);
            $payload['uuid']             = (string) Str::uuid();
            $payload['tenant_id']        = $tenantId;
            $payload['reference_number'] = $referenceNumber;
            $payload['status']           = 'pending';

            return $this->repository->create($payload);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function find(mixed $id): mixed
    {
        $transaction = $this->repository->find($id);
        if (! $transaction) {
            throw new NotFoundException('Transaction', $id);
        }

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);

        $repo = clone $this->repository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }
}
