<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SystemUserCollectionResource extends ResourceCollection
{
    public $collects = SystemUserResource::class;

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
                if ($v instanceof SystemUserResource) {
                    return $v->toArray($request);
                }

                return (new SystemUserResource($v))->toArray($request);
            })
            ->all();
    }
}
