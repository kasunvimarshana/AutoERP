<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseInvoiceLineCollection extends ResourceCollection
{
    public $collects = PurchaseInvoiceLineResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof PurchaseInvoiceLineResource) {
                    return $item->toArray($request);
                }

                return (new PurchaseInvoiceLineResource($item))->toArray($request);
            })
            ->all();
    }
}
