<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCategoryCollection extends ResourceCollection
{
    /** @var class-string<ProductCategoryResource> */
    public $collects = ProductCategoryResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productCategory) use ($request): array {
                if ($productCategory instanceof ProductCategoryResource) {
                    return $productCategory->toArray($request);
                }

                return (new ProductCategoryResource($productCategory))->toArray($request);
            })
            ->all();
    }
}
