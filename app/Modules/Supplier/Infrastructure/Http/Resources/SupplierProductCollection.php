<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierProductCollection extends ResourceCollection
{
    /** @var class-string<SupplierProductResource> */
    public $collects = SupplierProductResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $supplierProduct) use ($request): array {
                if ($supplierProduct instanceof SupplierProductResource) {
                    return $supplierProduct->toArray($request);
                }

                return (new SupplierProductResource($supplierProduct))->toArray($request);
            })
            ->all();
    }
}
