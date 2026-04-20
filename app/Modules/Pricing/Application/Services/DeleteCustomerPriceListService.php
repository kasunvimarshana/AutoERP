<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Domain\Exceptions\CustomerPriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\CustomerPriceListRepositoryInterface;

class DeleteCustomerPriceListService extends BaseService implements DeleteCustomerPriceListServiceInterface
{
    public function __construct(private readonly CustomerPriceListRepositoryInterface $customerPriceListRepository)
    {
        parent::__construct($customerPriceListRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $assignment = $this->customerPriceListRepository->find($id);

        if (! $assignment) {
            throw new CustomerPriceListNotFoundException($id);
        }

        return $this->customerPriceListRepository->delete($id);
    }
}
