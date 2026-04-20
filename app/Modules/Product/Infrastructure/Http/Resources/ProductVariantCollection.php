<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductVariantCollection extends ResourceCollection
{
    /** @var class-string<ProductVariantResource> */
    public $collects = ProductVariantResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productVariant) use ($request): array {
                if ($productVariant instanceof ProductVariantResource) {
                    return $productVariant->toArray($request);
                }

                return (new ProductVariantResource($productVariant))->toArray($request);
            })
            ->all();
    }
}
