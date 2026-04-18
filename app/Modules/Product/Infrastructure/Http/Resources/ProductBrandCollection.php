<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductBrandCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Product\Infrastructure\Http\Resources\ProductBrandResource> */
    public $collects = \Modules\Product\Infrastructure\Http\Resources\ProductBrandResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productBrand) use ($request): array {
                if ($productBrand instanceof \Modules\Product\Infrastructure\Http\Resources\ProductBrandResource) {
                    return $productBrand->toArray($request);
                }

                return (new \Modules\Product\Infrastructure\Http\Resources\ProductBrandResource($productBrand))->toArray($request);
            })
            ->all();
    }
}
