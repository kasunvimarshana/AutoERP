<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

class EloquentSupplierRepository extends EloquentRepository implements SupplierRepositoryInterface
{
    public function __construct(SupplierModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SupplierModel $model): Supplier => $this->mapModelToDomainEntity($model));
    }

    public function save(Supplier $supplier): Supplier
    {
        $data = [
            'tenant_id' => $supplier->getTenantId(),
            'user_id' => $supplier->getUserId(),
            'supplier_code' => $supplier->getSupplierCode(),
            'name' => $supplier->getName(),
            'type' => $supplier->getType(),
            'org_unit_id' => $supplier->getOrgUnitId(),
            'tax_number' => $supplier->getTaxNumber(),
            'registration_number' => $supplier->getRegistrationNumber(),
            'currency_id' => $supplier->getCurrencyId(),
            'payment_terms_days' => $supplier->getPaymentTermsDays(),
            'ap_account_id' => $supplier->getApAccountId(),
            'status' => $supplier->getStatus(),
            'notes' => $supplier->getNotes(),
            'metadata' => $supplier->getMetadata(),
            'row_version' => $supplier->getRowVersion(),
        ];

        if ($supplier->getId()) {
            $model = $this->update($supplier->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var SupplierModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndUserId(int $tenantId, int $userId): ?Supplier
    {
        /** @var SupplierModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndSupplierCode(int $tenantId, string $supplierCode): ?Supplier
    {
        /** @var SupplierModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_code', $supplierCode)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?Supplier
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(SupplierModel $model): Supplier
    {
        return new Supplier(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            userId: (int) $model->user_id,
            supplierCode: $model->supplier_code,
            name: (string) $model->name,
            type: (string) $model->type,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            taxNumber: $model->tax_number,
            registrationNumber: $model->registration_number,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            paymentTermsDays: (int) $model->payment_terms_days,
            apAccountId: $model->ap_account_id !== null ? (int) $model->ap_account_id : null,
            status: (string) $model->status,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
