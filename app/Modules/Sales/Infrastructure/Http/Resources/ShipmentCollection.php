<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ShipmentCollection extends ResourceCollection
{
    public $collects = ShipmentResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof ShipmentResource) {
                    return $item->toArray($request);
                }

                return (new ShipmentResource($item))->toArray($request);
            })
            ->all();
    }
}
