<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseOrderLineCollection extends ResourceCollection
{
    public $collects = PurchaseOrderLineResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof PurchaseOrderLineResource) {
                    return $item->toArray($request);
                }

                return (new PurchaseOrderLineResource($item))->toArray($request);
            })
            ->all();
    }
}
