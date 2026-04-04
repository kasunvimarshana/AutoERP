<?php
namespace Modules\Pricing\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListItemData;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Events\PriceListItemCreated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class CreatePriceListItemService implements CreatePriceListItemServiceInterface
{
    public function __construct(
        private readonly PriceListItemRepositoryInterface $repository,
        private readonly PriceListRepositoryInterface $priceListRepository,
    ) {}

    public function execute(PriceListItemData $data): PriceListItem
    {
        $item = $this->repository->create([
            'price_list_id'    => $data->priceListId,
            'product_id'       => $data->productId,
            'price'            => $data->price,
            'variant_id'       => $data->variantId,
            'min_qty'          => $data->minQty,
            'max_qty'          => $data->maxQty,
            'discount_percent' => $data->discountPercent,
            'uom'              => $data->uom,
        ]);

        $priceList = $this->priceListRepository->findById($data->priceListId);
        $tenantId = $priceList?->tenantId ?? 0;

        Event::dispatch(new PriceListItemCreated($tenantId, $item->id));

        return $item;
    }
}
