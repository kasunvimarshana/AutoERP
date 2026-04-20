<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GrnHeaderCollection extends ResourceCollection
{
    public $collects = GrnHeaderResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof GrnHeaderResource) {
                    return $item->toArray($request);
                }

                return (new GrnHeaderResource($item))->toArray($request);
            })
            ->all();
    }
}
