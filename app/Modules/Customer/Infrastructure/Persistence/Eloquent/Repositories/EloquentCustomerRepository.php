<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    public function __construct(CustomerModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CustomerModel $model): Customer => $this->mapModelToDomainEntity($model));
    }

    public function save(Customer $customer): Customer
    {
        $data = [
            'tenant_id' => $customer->getTenantId(),
            'user_id' => $customer->getUserId(),
            'customer_code' => $customer->getCustomerCode(),
            'name' => $customer->getName(),
            'type' => $customer->getType(),
            'org_unit_id' => $customer->getOrgUnitId(),
            'tax_number' => $customer->getTaxNumber(),
            'registration_number' => $customer->getRegistrationNumber(),
            'currency_id' => $customer->getCurrencyId(),
            'credit_limit' => $customer->getCreditLimit(),
            'payment_terms_days' => $customer->getPaymentTermsDays(),
            'ar_account_id' => $customer->getArAccountId(),
            'status' => $customer->getStatus(),
            'notes' => $customer->getNotes(),
            'metadata' => $customer->getMetadata(),
            'row_version' => $customer->getRowVersion(),
        ];

        if ($customer->getId()) {
            $model = $this->update($customer->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var CustomerModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndUserId(int $tenantId, int $userId): ?Customer
    {
        /** @var CustomerModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndCustomerCode(int $tenantId, string $customerCode): ?Customer
    {
        /** @var CustomerModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_code', $customerCode)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?Customer
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(CustomerModel $model): Customer
    {
        return new Customer(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            userId: (int) $model->user_id,
            customerCode: $model->customer_code,
            name: (string) $model->name,
            type: (string) $model->type,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            taxNumber: $model->tax_number,
            registrationNumber: $model->registration_number,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            creditLimit: number_format((float) $model->credit_limit, 6, '.', ''),
            paymentTermsDays: (int) $model->payment_terms_days,
            arAccountId: $model->ar_account_id !== null ? (int) $model->ar_account_id : null,
            status: (string) $model->status,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
