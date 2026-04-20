<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierProductCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Supplier\Infrastructure\Http\Resources\SupplierProductResource> */
    public $collects = \Modules\Supplier\Infrastructure\Http\Resources\SupplierProductResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $supplierProduct) use ($request): array {
                if ($supplierProduct instanceof \Modules\Supplier\Infrastructure\Http\Resources\SupplierProductResource) {
                    return $supplierProduct->toArray($request);
                }

                return (new \Modules\Supplier\Infrastructure\Http\Resources\SupplierProductResource($supplierProduct))->toArray($request);
            })
            ->all();
    }
}
