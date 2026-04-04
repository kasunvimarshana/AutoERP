<?php
namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class DeletePriceListService implements DeletePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $repository) {}

    public function execute(PriceList $priceList): bool
    {
        return $this->repository->delete($priceList);
    }
}
