<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /** @var class-string<ProductResource> */
    public $collects = ProductResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $product) use ($request): array {
                if ($product instanceof ProductResource) {
                    return $product->toArray($request);
                }

                return (new ProductResource($product))->toArray($request);
            })
            ->all();
    }
}
