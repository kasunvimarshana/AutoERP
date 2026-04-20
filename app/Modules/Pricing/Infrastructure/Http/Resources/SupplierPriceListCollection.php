<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierPriceListCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Pricing\Infrastructure\Http\Resources\SupplierPriceListResource> */
    public $collects = \Modules\Pricing\Infrastructure\Http\Resources\SupplierPriceListResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $assignment) use ($request): array {
                if ($assignment instanceof \Modules\Pricing\Infrastructure\Http\Resources\SupplierPriceListResource) {
                    return $assignment->toArray($request);
                }

                return (new \Modules\Pricing\Infrastructure\Http\Resources\SupplierPriceListResource($assignment))->toArray($request);
            })
            ->all();
    }
}
