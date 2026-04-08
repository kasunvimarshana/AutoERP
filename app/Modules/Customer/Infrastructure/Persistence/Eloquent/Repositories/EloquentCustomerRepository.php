<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
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

    public function findByCode(int $tenantId, string $code): ?Customer
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->toDomainCollection($this->model->where('tenant_id', $tenantId)->get());
    }

    public function findByUserId(int $tenantId, int $userId): ?Customer
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('user_id', $userId)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Customer $customer): Customer
    {
        $data = [
            'tenant_id'        => $customer->getTenantId(),
            'user_id'          => $customer->getUserId(),
            'name'             => $customer->getName(),
            'code'             => $customer->getCode(),
            'email'            => $customer->getEmail(),
            'phone'            => $customer->getPhone(),
            'billing_address'  => $customer->getBillingAddress(),
            'shipping_address' => $customer->getShippingAddress(),
            'date_of_birth'    => $customer->getDateOfBirth(),
            'loyalty_tier'     => $customer->getLoyaltyTier(),
            'credit_limit'     => $customer->getCreditLimit(),
            'payment_terms'    => $customer->getPaymentTerms(),
            'currency'         => $customer->getCurrency(),
            'tax_number'       => $customer->getTaxNumber(),
            'status'           => $customer->getStatus(),
            'type'             => $customer->getType(),
            'attributes'       => $customer->getAttributes(),
            'metadata'         => $customer->getMetadata(),
        ];

        if ($customer->getId()) {
            $model = $this->update($customer->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    private function mapModelToDomainEntity(CustomerModel $model): Customer
    {
        return new Customer(
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            userId: $model->user_id,
            email: $model->email,
            phone: $model->phone,
            billingAddress: $model->billing_address,
            shippingAddress: $model->shipping_address,
            dateOfBirth: $model->date_of_birth,
            loyaltyTier: $model->loyalty_tier,
            creditLimit: $model->credit_limit,
            paymentTerms: $model->payment_terms,
            currency: $model->currency,
            taxNumber: $model->tax_number,
            status: $model->status,
            type: $model->type,
            attributes: $model->attributes,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
