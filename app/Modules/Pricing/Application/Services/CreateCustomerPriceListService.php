<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Pricing\Application\Contracts\CreateCustomerPriceListServiceInterface;
use Modules\Pricing\Application\DTOs\CustomerPriceListData;
use Modules\Pricing\Domain\Entities\CustomerPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class CreateCustomerPriceListService extends BaseService implements CreateCustomerPriceListServiceInterface
{
    public function __construct(
        private readonly CustomerPriceListRepositoryInterface $customerPriceListRepository,
        private readonly PriceListRepositoryInterface $priceListRepository,
    ) {
        parent::__construct($customerPriceListRepository);
    }

    protected function handle(array $data): CustomerPriceList
    {
        $dto = CustomerPriceListData::fromArray($data);

        $priceList = $this->priceListRepository->find($dto->price_list_id);
        if (! $priceList) {
            throw new DomainException('Price list not found.');
        }

        if ($priceList->getType() !== 'sales') {
            throw new DomainException('Only sales price lists can be assigned to customers.');
        }

        $assignment = new CustomerPriceList(
            tenantId: $priceList->getTenantId(),
            customerId: $dto->customer_id,
            priceListId: $dto->price_list_id,
            priority: $dto->priority,
        );

        return $this->customerPriceListRepository->save($assignment);
    }
}
