<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierAddressCollection extends ResourceCollection
{
    /** @var class-string<SupplierAddressResource> */
    public $collects = SupplierAddressResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $address) use ($request): array {
                if ($address instanceof SupplierAddressResource) {
                    return $address->toArray($request);
                }

                return (new SupplierAddressResource($address))->toArray($request);
            })
            ->all();
    }
}
