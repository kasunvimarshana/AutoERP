<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollectionResource extends ResourceCollection
{
    public $collects = CustomerResource::class;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return $this->collection
            ->map(static function (mixed $v) use ($request): array {
                if ($v instanceof CustomerResource) {
                    return $v->toArray($request);
                }

                return (new CustomerResource($v))->toArray($request);
            })
            ->all();
    }
}
