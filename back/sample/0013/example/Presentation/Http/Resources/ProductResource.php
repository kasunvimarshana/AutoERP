<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProductResource — Transforms a Product domain entity into an API JSON response.
 *
 * The resource sits at the Presentation layer boundary and ensures the domain
 * model is never exposed directly to API consumers.
 *
 * @property \App\Domain\Catalog\Entities\Product $resource
 */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->id()->value(),
            'name'       => $this->resource->name()->value(),
            'price'      => [
                'amount'    => $this->resource->price()->amount(),
                'currency'  => $this->resource->price()->currency(),
                'formatted' => $this->resource->price()->formatted(),
            ],
            'active'     => $this->resource->isActive(),
            'created_at' => $this->resource->createdAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
