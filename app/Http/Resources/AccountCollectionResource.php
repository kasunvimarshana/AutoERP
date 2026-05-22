<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountCollectionResource extends ResourceCollection
{
    public $collects = AccountResource::class;

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
                if ($v instanceof AccountResource) {
                    return $v->toArray($request);
                }

                return (new AccountResource($v))->toArray($request);
            })
            ->all();
    }
}
