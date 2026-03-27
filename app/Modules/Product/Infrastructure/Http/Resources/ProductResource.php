<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'sku'         => $this->getSku()->value(),
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'price'       => [
                'amount'   => $this->getPrice()->getAmount(),
                'currency' => $this->getPrice()->getCurrency(),
            ],
            'category'    => $this->getCategory(),
            'status'      => $this->getStatus(),
            'attributes'  => $this->getAttributes(),
            'metadata'    => $this->getMetadata(),
            'images'      => ProductImageResource::collection($this->getImages()),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
