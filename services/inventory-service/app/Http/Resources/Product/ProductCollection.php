<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Product Collection Resource
 */
class ProductCollection extends ResourceCollection
{
    public $collects = ProductResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => method_exists($this->resource, 'total') ? $this->resource->total() : count($this->collection),
                'per_page' => method_exists($this->resource, 'perPage') ? $this->resource->perPage() : null,
                'current_page' => method_exists($this->resource, 'currentPage') ? $this->resource->currentPage() : null,
                'last_page' => method_exists($this->resource, 'lastPage') ? $this->resource->lastPage() : null,
            ],
        ];
    }
}
