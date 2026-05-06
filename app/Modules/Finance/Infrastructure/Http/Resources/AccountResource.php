<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'parent_id' => $this->resource->getParentId(),
            'code' => $this->resource->getCode(),
            'name' => $this->resource->getName(),
            'type' => $this->resource->getType(),
            'sub_type' => $this->resource->getSubType(),
            'normal_balance' => $this->resource->getNormalBalance(),
            'is_system' => $this->resource->isSystem(),
            'is_bank_account' => $this->resource->isBankAccount(),
            'is_credit_card' => $this->resource->isCreditCard(),
            'currency_id' => $this->resource->getCurrencyId(),
            'description' => $this->resource->getDescription(),
            'is_active' => $this->resource->isActive(),
            'path' => $this->resource->getPath(),
            'depth' => $this->resource->getDepth(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
