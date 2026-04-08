<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class BankTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->resource->id,
            'tenant_id'        => $this->resource->tenantId,
            'bank_account_id'  => $this->resource->bankAccountId,
            'date'             => $this->resource->date->format('Y-m-d'),
            'description'      => $this->resource->description,
            'amount'           => $this->resource->amount,
            'type'             => $this->resource->type,
            'status'           => $this->resource->status,
            'source'           => $this->resource->source,
            'account_id'       => $this->resource->accountId,
            'journal_entry_id' => $this->resource->journalEntryId,
            'reference'        => $this->resource->reference,
            'metadata'         => $this->resource->metadata,
            'created_at'       => $this->resource->createdAt,
            'updated_at'       => $this->resource->updatedAt,
        ];
    }
}
