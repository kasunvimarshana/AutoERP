<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->resource->id,
            'tenant_id'          => $this->resource->tenantId,
            'account_id'         => $this->resource->accountId,
            'name'               => $this->resource->name,
            'account_type'       => $this->resource->accountType,
            'bank_name'          => $this->resource->bankName,
            'account_number'     => $this->resource->accountNumber,
            'currency_code'      => $this->resource->currencyCode,
            'current_balance'    => $this->resource->currentBalance,
            'last_reconciled_at' => $this->resource->lastReconciledAt?->format('Y-m-d H:i:s'),
            'is_active'          => $this->resource->isActive,
            'created_at'         => $this->resource->createdAt,
            'updated_at'         => $this->resource->updatedAt,
        ];
    }
}
