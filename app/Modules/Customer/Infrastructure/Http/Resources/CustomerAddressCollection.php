<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerAddressCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Customer\Infrastructure\Http\Resources\CustomerAddressResource> */
    public $collects = \Modules\Customer\Infrastructure\Http\Resources\CustomerAddressResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $address) use ($request): array {
                if ($address instanceof \Modules\Customer\Infrastructure\Http\Resources\CustomerAddressResource) {
                    return $address->toArray($request);
                }

                return (new \Modules\Customer\Infrastructure\Http\Resources\CustomerAddressResource($address))->toArray($request);
            })
            ->all();
    }
}
