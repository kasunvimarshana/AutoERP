<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerAddressCollection extends ResourceCollection
{
    /** @var class-string<CustomerAddressResource> */
    public $collects = CustomerAddressResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $address) use ($request): array {
                if ($address instanceof CustomerAddressResource) {
                    return $address->toArray($request);
                }

                return (new CustomerAddressResource($address))->toArray($request);
            })
            ->all();
    }
}
