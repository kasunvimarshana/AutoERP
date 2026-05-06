<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'fiscal_period_id' => $this->resource->getFiscalPeriodId(),
            'entry_number' => $this->resource->getEntryNumber(),
            'entry_type' => $this->resource->getEntryType(),
            'reference_type' => $this->resource->getReferenceType(),
            'reference_id' => $this->resource->getReferenceId(),
            'description' => $this->resource->getDescription(),
            'entry_date' => $this->resource->getEntryDate()->format('Y-m-d'),
            'posting_date' => $this->resource->getPostingDate()?->format('Y-m-d'),
            'status' => $this->resource->getStatus(),
            'is_reversed' => $this->resource->isReversed(),
            'reversal_entry_id' => $this->resource->getReversalEntryId(),
            'created_by' => $this->resource->getCreatedBy(),
            'posted_by' => $this->resource->getPostedBy(),
            'posted_at' => $this->resource->getPostedAt(),
            'lines' => array_map(static fn ($line): array => [
                'id' => $line->getId(),
                'account_id' => $line->getAccountId(),
                'description' => $line->getDescription(),
                'debit_amount' => $line->getDebitAmount(),
                'credit_amount' => $line->getCreditAmount(),
                'currency_id' => $line->getCurrencyId(),
                'exchange_rate' => $line->getExchangeRate(),
                'base_debit_amount' => $line->getBaseDebitAmount(),
                'base_credit_amount' => $line->getBaseCreditAmount(),
                'cost_center_id' => $line->getCostCenterId(),
                'metadata' => $line->getMetadata(),
            ], $this->resource->getLines()),
            'row_version' => $this->resource->getRowVersion(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
