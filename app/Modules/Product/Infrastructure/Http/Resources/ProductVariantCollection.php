<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductVariantCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Product\Infrastructure\Http\Resources\ProductVariantResource> */
    public $collects = \Modules\Product\Infrastructure\Http\Resources\ProductVariantResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productVariant) use ($request): array {
                if ($productVariant instanceof \Modules\Product\Infrastructure\Http\Resources\ProductVariantResource) {
                    return $productVariant->toArray($request);
                }

                return (new \Modules\Product\Infrastructure\Http\Resources\ProductVariantResource($productVariant))->toArray($request);
            })
            ->all();
    }
}
