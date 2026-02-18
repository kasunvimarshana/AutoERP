<?php

declare(strict_types=1);

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Base API Resource Collection
 *
 * Provides consistent collection response formatting with pagination support.
 *
 * This class:
 * - Wraps collections with consistent structure
 * - Includes pagination meta data
 * - Supports filtering and sorting metadata
 * - Enables collection-level customization
 *
 * Usage:
 * 1. Return collection in controller: return CustomerResource::collection($customers)
 * 2. Or extend for custom collection: class CustomerCollection extends BaseResourceCollection
 *
 * Example:
 * return CustomerResource::collection($customers)
 *     ->additional(['meta' => ['version' => 'v1']]);
 */
class BaseResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
