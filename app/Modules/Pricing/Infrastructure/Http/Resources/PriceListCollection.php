<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PriceListCollection extends ResourceCollection
{
    /** @var class-string<PriceListResource> */
    public $collects = PriceListResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $priceList) use ($request): array {
                if ($priceList instanceof PriceListResource) {
                    return $priceList->toArray($request);
                }

                return (new PriceListResource($priceList))->toArray($request);
            })
            ->all();
    }
}
