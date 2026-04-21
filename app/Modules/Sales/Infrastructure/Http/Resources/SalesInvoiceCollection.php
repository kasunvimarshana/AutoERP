<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesInvoiceCollection extends ResourceCollection
{
    public $collects = SalesInvoiceResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $item) use ($request): array {
                if ($item instanceof SalesInvoiceResource) {
                    return $item->toArray($request);
                }

                return (new SalesInvoiceResource($item))->toArray($request);
            })
            ->all();
    }
}
