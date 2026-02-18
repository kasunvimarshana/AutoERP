<?php

declare(strict_types=1);

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API Resource
 *
 * Provides a foundation for all API resource transformers following
 * REST API best practices and consistent response formatting.
 *
 * This class provides:
 * - Consistent response structure
 * - Conditional attribute inclusion
 * - Relationship loading
 * - Meta data support
 *
 * Resources implement the Adapter pattern from Clean Architecture,
 * transforming domain models into API responses.
 *
 * Usage:
 * 1. Extend this class in your module's resources
 * 2. Implement toArray() method
 * 3. Return resource in controller: return new CustomerResource($customer)
 *
 * Example:
 * class CustomerResource extends BaseResource {
 *     public function toArray(Request $request): array {
 *         return [
 *             'id' => $this->id,
 *             'name' => $this->name,
 *             'email' => $this->email,
 *             'created_at' => $this->created_at->toIso8601String(),
 *             'orders' => OrderResource::collection($this->whenLoaded('orders')),
 *         ];
 *     }
 * }
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Include resource type in response.
     * Set this property in child classes.
     */
    protected ?string $resourceType = null;

    /**
     * Transform the resource into an array.
     */
    abstract public function toArray(Request $request): array;

    /**
     * Get additional data that should be returned with the resource array.
     * Can be overridden to add meta information.
     */
    public function with(Request $request): array
    {
        $with = [];

        if ($this->resourceType) {
            $with['type'] = $this->resourceType;
        }

        return $with;
    }

    /**
     * Customize the response for a request.
     * Can be overridden for custom response formatting.
     */
    public function withResponse(Request $request, $response): void
    {
        // Add custom headers or modify response if needed
    }
}
