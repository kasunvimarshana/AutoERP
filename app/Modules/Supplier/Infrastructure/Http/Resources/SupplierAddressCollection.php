<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierAddressCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Supplier\Infrastructure\Http\Resources\SupplierAddressResource> */
    public $collects = \Modules\Supplier\Infrastructure\Http\Resources\SupplierAddressResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $address) use ($request): array {
                if ($address instanceof \Modules\Supplier\Infrastructure\Http\Resources\SupplierAddressResource) {
                    return $address->toArray($request);
                }

                return (new \Modules\Supplier\Infrastructure\Http\Resources\SupplierAddressResource($address))->toArray($request);
            })
            ->all();
    }
}
