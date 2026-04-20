<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductBrandCollection extends ResourceCollection
{
    /** @var class-string<ProductBrandResource> */
    public $collects = ProductBrandResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productBrand) use ($request): array {
                if ($productBrand instanceof ProductBrandResource) {
                    return $productBrand->toArray($request);
                }

                return (new ProductBrandResource($productBrand))->toArray($request);
            })
            ->all();
    }
}
