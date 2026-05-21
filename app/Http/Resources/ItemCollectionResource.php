<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ItemCollectionResource extends ResourceCollection
{
    public $collects = ItemResource::class;

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
                if ($v instanceof ItemResource) {
                    return $v->toArray($request);
                }

                return (new ItemResource($v))->toArray($request);
            })
            ->all();
    }
}
