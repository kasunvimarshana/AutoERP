<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesReturnCollection extends ResourceCollection
{
    public $collects = SalesReturnResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof SalesReturnResource) {
                    return $item->toArray($request);
                }

                return (new SalesReturnResource($item))->toArray($request);
            })
            ->all();
    }
}
