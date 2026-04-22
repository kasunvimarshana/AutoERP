<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PriceListItemCollection extends ResourceCollection
{
    /** @var class-string<PriceListItemResource> */
    public $collects = PriceListItemResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $priceListItem) use ($request): array {
                if ($priceListItem instanceof PriceListItemResource) {
                    return $priceListItem->toArray($request);
                }

                return (new PriceListItemResource($priceListItem))->toArray($request);
            })
            ->all();
    }
}
