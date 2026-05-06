<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankCategoryRuleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'bank_account_id' => $this->resource->getBankAccountId(),
            'name' => $this->resource->getName(),
            'priority' => $this->resource->getPriority(),
            'conditions' => $this->resource->getConditions(),
            'account_id' => $this->resource->getAccountId(),
            'description_template' => $this->resource->getDescriptionTemplate(),
            'is_active' => $this->resource->isActive(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
