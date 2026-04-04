<?php
namespace Modules\Pricing\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListUpdated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class UpdatePriceListService implements UpdatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $repository) {}

    public function execute(PriceList $priceList, array $data): PriceList
    {
        $updated = $this->repository->update($priceList, $data);

        Event::dispatch(new PriceListUpdated($priceList->tenantId, $priceList->id));

        return $updated;
    }
}
