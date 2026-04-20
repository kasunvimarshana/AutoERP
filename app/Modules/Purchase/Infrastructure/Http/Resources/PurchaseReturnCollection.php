<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseReturnCollection extends ResourceCollection
{
    public $collects = PurchaseReturnResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof PurchaseReturnResource) {
                    return $item->toArray($request);
                }

                return (new PurchaseReturnResource($item))->toArray($request);
            })
            ->all();
    }
}
