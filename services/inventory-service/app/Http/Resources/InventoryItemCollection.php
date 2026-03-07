<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryItemCollection extends ResourceCollection
{
    public $collects = InventoryItemResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page'  => $this->resource->currentPage(),
                'per_page'      => $this->resource->perPage(),
                'total'         => $this->resource->total(),
                'last_page'     => $this->resource->lastPage(),
                'from'          => $this->resource->firstItem(),
                'to'            => $this->resource->lastItem(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last'  => $this->resource->url($this->resource->lastPage()),
                'prev'  => $this->resource->previousPageUrl(),
                'next'  => $this->resource->nextPageUrl(),
            ],
        ];
    }
}
