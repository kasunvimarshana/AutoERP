<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VoucherResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'tenant_id' => $this->resource['tenant_id'] ?? null,
            'organization_unit_id' => $this->resource['organization_unit_id'] ?? null,
            'voucher_type_id' => $this->resource['voucher_type_id'] ?? null,
            'voucher_number' => $this->resource['voucher_number'] ?? null,
            'voucher_date' => $this->resource['voucher_date'] ?? null,
            'party_type' => $this->resource['party_type'] ?? null,
            'party_id' => $this->resource['party_id'] ?? null,
            'direction' => $this->resource['direction'] ?? null,
            'currency_id' => $this->resource['currency_id'] ?? null,
            'exchange_rate' => $this->resource['exchange_rate'] ?? null,
            'total_debit' => $this->resource['total_debit'] ?? null,
            'total_credit' => $this->resource['total_credit'] ?? null,
            'total_amount' => $this->resource['total_amount'] ?? null,
            'status' => $this->resource['status'] ?? null,
            'payment_method_id' => $this->resource['payment_method_id'] ?? null,
            'cash_account_id' => $this->resource['cash_account_id'] ?? null,
            'bank_account_id' => $this->resource['bank_account_id'] ?? null,
            'document_id' => $this->resource['document_id'] ?? null,
            'journal_entry_id' => $this->resource['journal_entry_id'] ?? null,
            'payment_id' => $this->resource['payment_id'] ?? null,
            'reference_type' => $this->resource['reference_type'] ?? null,
            'reference_id' => $this->resource['reference_id'] ?? null,
            'remarks' => $this->resource['remarks'] ?? null,
            'metadata' => $this->resource['metadata'] ?? [],
            'lines' => $this->resource['lines'] ?? [],
            'allocations' => $this->resource['allocations'] ?? [],
            'created_at' => $this->resource['created_at'] ?? null,
            'updated_at' => $this->resource['updated_at'] ?? null,
        ];
    }
}
