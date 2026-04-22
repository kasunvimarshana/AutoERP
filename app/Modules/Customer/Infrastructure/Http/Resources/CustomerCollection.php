<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    /** @var class-string<CustomerResource> */
    public $collects = CustomerResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $customer) use ($request): array {
                if ($customer instanceof CustomerResource) {
                    return $customer->toArray($request);
                }

                return (new CustomerResource($customer))->toArray($request);
            })
            ->all();
    }
}
