<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseInvoiceCollection extends ResourceCollection
{
    public $collects = PurchaseInvoiceResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof PurchaseInvoiceResource) {
                    return $item->toArray($request);
                }

                return (new PurchaseInvoiceResource($item))->toArray($request);
            })
            ->all();
    }
}
