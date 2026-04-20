<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PriceListCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Pricing\Infrastructure\Http\Resources\PriceListResource> */
    public $collects = \Modules\Pricing\Infrastructure\Http\Resources\PriceListResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $priceList) use ($request): array {
                if ($priceList instanceof \Modules\Pricing\Infrastructure\Http\Resources\PriceListResource) {
                    return $priceList->toArray($request);
                }

                return (new \Modules\Pricing\Infrastructure\Http\Resources\PriceListResource($priceList))->toArray($request);
            })
            ->all();
    }
}
