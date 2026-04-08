<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Transaction\Domain\Entities\JournalEntry;
use Modules\Transaction\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

class EloquentJournalEntryRepository extends EloquentRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntryModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (JournalEntryModel $m): JournalEntry => $this->mapModelToDomainEntity($m));
    }

    public function save(JournalEntry $journalEntry): JournalEntry
    {
        $savedModel = null;
        DB::transaction(function () use ($journalEntry, &$savedModel) {
            $data = [
                'tenant_id'      => $journalEntry->getTenantId(),
                'transaction_id' => $journalEntry->getTransactionId(),
                'account_code'   => $journalEntry->getAccountCode(),
                'account_name'   => $journalEntry->getAccountName(),
                'debit_amount'   => $journalEntry->getDebitAmount(),
                'credit_amount'  => $journalEntry->getCreditAmount(),
                'description'    => $journalEntry->getDescription(),
                'status'         => $journalEntry->getStatus(),
                'posted_at'      => $journalEntry->getPostedAt()?->format('Y-m-d H:i:s'),
                'metadata'       => $journalEntry->getMetadata()->toArray(),
            ];
            if ($journalEntry->getId()) {
                $savedModel = $this->update($journalEntry->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof JournalEntryModel) {
            throw new \RuntimeException('Failed to save JournalEntry.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?JournalEntry
    {
        $model = $this->model->newQuery()->find($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByTransaction(int $transactionId): Collection
    {
        return $this->model
            ->where('transaction_id', $transactionId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByAccountCode(int $tenantId, string $accountCode): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('account_code', $accountCode)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function list(array $filters = [], int $perPage = 15, int $page = 1): mixed
    {
        return $this->model->newQuery()
            ->when(isset($filters['tenant_id']), fn ($q) => $q->where('tenant_id', $filters['tenant_id']))
            ->when(isset($filters['transaction_id']), fn ($q) => $q->where('transaction_id', $filters['transaction_id']))
            ->when(isset($filters['account_code']), fn ($q) => $q->where('account_code', $filters['account_code']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function mapModelToDomainEntity(JournalEntryModel $model): JournalEntry
    {
        return new JournalEntry(
            tenantId:      $model->tenant_id,
            transactionId: $model->transaction_id,
            accountCode:   $model->account_code,
            accountName:   $model->account_name,
            debitAmount:   (float) $model->debit_amount,
            creditAmount:  (float) $model->credit_amount,
            description:   $model->description,
            status:        $model->status,
            postedAt:      $model->posted_at ? new \DateTimeImmutable($model->posted_at->format('c')) : null,
            metadata:      isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:            $model->id,
            createdAt:     $model->created_at,
            updatedAt:     $model->updated_at,
        );
    }
}
