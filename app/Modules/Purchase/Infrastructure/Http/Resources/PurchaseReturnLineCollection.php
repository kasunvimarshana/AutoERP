<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseReturnLineCollection extends ResourceCollection
{
    public $collects = PurchaseReturnLineResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof PurchaseReturnLineResource) {
                    return $item->toArray($request);
                }

                return (new PurchaseReturnLineResource($item))->toArray($request);
            })
            ->all();
    }
}
