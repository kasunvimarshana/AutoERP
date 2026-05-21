<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DepartmentCollectionResource extends ResourceCollection
{
    public $collects = DepartmentResource::class;

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
                if ($v instanceof DepartmentResource) {
                    return $v->toArray($request);
                }

                return (new DepartmentResource($v))->toArray($request);
            })
            ->all();
    }
}
