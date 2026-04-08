<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerCreated;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class CreateCustomerService extends BaseService implements CreateCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $customerRepository)
    {
        parent::__construct($customerRepository);
    }

    protected function handle(array $data): Customer
    {
        $dto = CustomerData::fromArray($data);

        $customer = new Customer(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            code: $dto->code,
            userId: $dto->user_id,
            email: $dto->email,
            phone: $dto->phone,
            billingAddress: $dto->billing_address,
            shippingAddress: $dto->shipping_address,
            dateOfBirth: $dto->date_of_birth,
            loyaltyTier: $dto->loyalty_tier,
            creditLimit: $dto->credit_limit,
            paymentTerms: $dto->payment_terms,
            currency: $dto->currency ?? 'USD',
            taxNumber: $dto->tax_number,
            status: $dto->status ?? 'active',
            type: $dto->type ?? 'retail',
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        $saved = $this->customerRepository->save($customer);

        $this->addEvent(new CustomerCreated($saved));

        return $saved;
    }
}
