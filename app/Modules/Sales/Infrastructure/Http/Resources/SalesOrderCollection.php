<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesOrderCollection extends ResourceCollection
{
    public $collects = SalesOrderResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof SalesOrderResource) {
                    return $item->toArray($request);
                }

                return (new SalesOrderResource($item))->toArray($request);
            })
            ->all();
    }
}
