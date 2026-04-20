<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductIdentifierCollection extends ResourceCollection
{
    /** @var class-string<ProductIdentifierResource> */
    public $collects = ProductIdentifierResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productIdentifier) use ($request): array {
                if ($productIdentifier instanceof ProductIdentifierResource) {
                    return $productIdentifier->toArray($request);
                }

                return (new ProductIdentifierResource($productIdentifier))->toArray($request);
            })
            ->all();
    }
}
