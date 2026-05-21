<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UOMCollectionResource extends ResourceCollection
{
    public $collects = UOMResource::class;

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
                if ($v instanceof UOMResource) {
                    return $v->toArray($request);
                }

                return (new UOMResource($v))->toArray($request);
            })
            ->all();
    }
}
