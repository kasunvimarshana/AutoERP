<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public $collects = ProductResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total'        => $this->resource instanceof \Illuminate\Pagination\AbstractPaginator
                    ? $this->resource->total()
                    : $this->collection->count(),
                'per_page'     => $this->resource instanceof \Illuminate\Pagination\AbstractPaginator
                    ? $this->resource->perPage()
                    : null,
                'current_page' => $this->resource instanceof \Illuminate\Pagination\AbstractPaginator
                    ? $this->resource->currentPage()
                    : null,
                'last_page'    => $this->resource instanceof \Illuminate\Pagination\AbstractPaginator
                    ? $this->resource->lastPage()
                    : null,
            ],
        ];
    }
}
