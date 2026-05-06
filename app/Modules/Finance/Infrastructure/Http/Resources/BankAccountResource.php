<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'account_id' => $this->resource->getAccountId(),
            'name' => $this->resource->getName(),
            'bank_name' => $this->resource->getBankName(),
            'account_number' => $this->resource->getAccountNumber(),
            'routing_number' => $this->resource->getRoutingNumber(),
            'currency_id' => $this->resource->getCurrencyId(),
            'current_balance' => $this->resource->getCurrentBalance(),
            'last_sync_at' => $this->resource->getLastSyncAt()?->format(\DateTimeInterface::ATOM),
            'feed_provider' => $this->resource->getFeedProvider(),
            'is_active' => $this->resource->isActive(),
            'row_version' => $this->resource->getRowVersion(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
