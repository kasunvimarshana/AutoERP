<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    /** @var class-string<\Modules\Customer\Infrastructure\Http\Resources\CustomerResource> */
    public $collects = \Modules\Customer\Infrastructure\Http\Resources\CustomerResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $customer) use ($request): array {
                if ($customer instanceof \Modules\Customer\Infrastructure\Http\Resources\CustomerResource) {
                    return $customer->toArray($request);
                }

                return (new \Modules\Customer\Infrastructure\Http\Resources\CustomerResource($customer))->toArray($request);
            })
            ->all();
    }
}
