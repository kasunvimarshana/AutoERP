<?php
namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pricing\Domain\Entities\PriceListItem;

class PriceListItemResource extends JsonResource
{
    public function __construct(private readonly PriceListItem $item) { parent::__construct($item); }

    public function toArray($request): array
    {
        return [
            'id'               => $this->item->id,
            'price_list_id'    => $this->item->priceListId,
            'product_id'       => $this->item->productId,
            'price'            => $this->item->price,
            'variant_id'       => $this->item->variantId,
            'min_qty'          => $this->item->minQty,
            'max_qty'          => $this->item->maxQty,
            'discount_percent' => $this->item->discountPercent,
            'uom'              => $this->item->uom,
        ];
    }
}
