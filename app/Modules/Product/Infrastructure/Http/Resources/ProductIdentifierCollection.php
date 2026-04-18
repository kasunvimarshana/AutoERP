<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductIdentifierCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Product\Infrastructure\Http\Resources\ProductIdentifierResource> */
    public $collects = \Modules\Product\Infrastructure\Http\Resources\ProductIdentifierResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $productIdentifier) use ($request): array {
                if ($productIdentifier instanceof \Modules\Product\Infrastructure\Http\Resources\ProductIdentifierResource) {
                    return $productIdentifier->toArray($request);
                }

                return (new \Modules\Product\Infrastructure\Http\Resources\ProductIdentifierResource($productIdentifier))->toArray($request);
            })
            ->all();
    }
}
