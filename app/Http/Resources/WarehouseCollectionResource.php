<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WarehouseCollectionResource extends ResourceCollection
{
    public $collects = WarehouseResource::class;

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
                if ($v instanceof WarehouseResource) {
                    return $v->toArray($request);
                }

                return (new WarehouseResource($v))->toArray($request);
            })
            ->all();
    }
}
