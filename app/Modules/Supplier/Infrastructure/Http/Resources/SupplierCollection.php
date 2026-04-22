<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierCollection extends ResourceCollection
{
    /** @var class-string<SupplierResource> */
    public $collects = SupplierResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $supplier) use ($request): array {
                if ($supplier instanceof SupplierResource) {
                    return $supplier->toArray($request);
                }

                return (new SupplierResource($supplier))->toArray($request);
            })
            ->all();
    }
}
