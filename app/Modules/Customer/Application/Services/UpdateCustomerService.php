<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerUpdated;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class UpdateCustomerService extends BaseService implements UpdateCustomerServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $customerRepository)
    {
        parent::__construct($customerRepository);
    }

    protected function handle(array $data): Customer
    {
        $id       = $data['id'];
        $customer = $this->customerRepository->find($id);

        if (! $customer) {
            throw new CustomerNotFoundException($id);
        }

        $dto = CustomerData::fromArray($data);

        $customer->updateDetails(
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
            currency: $dto->currency ?? $customer->getCurrency(),
            taxNumber: $dto->tax_number,
            type: $dto->type ?? $customer->getType(),
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        if (isset($dto->status)) {
            if ($dto->status === 'active') {
                $customer->activate();
            } elseif ($dto->status === 'inactive') {
                $customer->deactivate();
            }
        }

        $saved = $this->customerRepository->save($customer);

        $this->addEvent(new CustomerUpdated($saved));

        return $saved;
    }
}
