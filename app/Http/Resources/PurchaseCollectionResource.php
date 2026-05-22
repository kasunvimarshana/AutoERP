<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseCollectionResource extends ResourceCollection
{
    public $collects = PurchaseResource::class;

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
                if ($v instanceof PurchaseResource) {
                    return $v->toArray($request);
                }

                return (new PurchaseResource($v))->toArray($request);
            })
            ->all();
    }
}
