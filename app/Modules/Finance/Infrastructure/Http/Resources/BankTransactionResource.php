<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'bank_account_id' => $this->resource->getBankAccountId(),
            'external_id' => $this->resource->getExternalId(),
            'transaction_date' => $this->resource->getTransactionDate()->format('Y-m-d'),
            'description' => $this->resource->getDescription(),
            'amount' => $this->resource->getAmount(),
            'balance' => $this->resource->getBalance(),
            'type' => $this->resource->getType(),
            'status' => $this->resource->getStatus(),
            'matched_journal_entry_id' => $this->resource->getMatchedJournalEntryId(),
            'category_rule_id' => $this->resource->getCategoryRuleId(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
