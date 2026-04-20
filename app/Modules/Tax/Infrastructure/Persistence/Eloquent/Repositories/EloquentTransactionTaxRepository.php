<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tax\Domain\Entities\TransactionTax;
use Modules\Tax\Domain\RepositoryInterfaces\TransactionTaxRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TransactionTaxModel;

class EloquentTransactionTaxRepository extends EloquentRepository implements TransactionTaxRepositoryInterface
{
    public function __construct(TransactionTaxModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TransactionTaxModel $model): TransactionTax => $this->mapModelToDomainEntity($model));
    }

    public function saveManyForReference(int $tenantId, string $referenceType, int $referenceId, array $taxLines): array
    {
        $saved = [];

        foreach ($taxLines as $line) {
            /** @var TransactionTaxModel $model */
            $model = $this->create([
                'tenant_id' => $tenantId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'tax_rate_id' => (int) $line['tax_rate_id'],
                'taxable_amount' => $line['taxable_amount'],
                'tax_amount' => $line['tax_amount'],
                'tax_account_id' => (int) $line['tax_account_id'],
            ]);

            $saved[] = $this->toDomainEntity($model);
        }

        return $saved;
    }

    public function deleteByReference(int $tenantId, string $referenceType, int $referenceId): void
    {
        $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    public function listByReference(int $tenantId, string $referenceType, int $referenceId): array
    {
        /** @var \Illuminate\Support\Collection<int, TransactionTaxModel> $models */
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->orderBy('id')
            ->get();

        return $models->map(fn (TransactionTaxModel $model): TransactionTax => $this->toDomainEntity($model))->all();
    }

    public function find(int|string $id, array $columns = ['*']): ?TransactionTax
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(TransactionTaxModel $model): TransactionTax
    {
        return new TransactionTax(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            referenceType: (string) $model->reference_type,
            referenceId: (int) $model->reference_id,
            taxRateId: (int) $model->tax_rate_id,
            taxableAmount: (string) $model->taxable_amount,
            taxAmount: (string) $model->tax_amount,
            taxAccountId: (int) $model->tax_account_id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
