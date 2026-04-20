<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerPriceListCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Pricing\Infrastructure\Http\Resources\CustomerPriceListResource> */
    public $collects = \Modules\Pricing\Infrastructure\Http\Resources\CustomerPriceListResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $assignment) use ($request): array {
                if ($assignment instanceof \Modules\Pricing\Infrastructure\Http\Resources\CustomerPriceListResource) {
                    return $assignment->toArray($request);
                }

                return (new \Modules\Pricing\Infrastructure\Http\Resources\CustomerPriceListResource($assignment))->toArray($request);
            })
            ->all();
    }
}
