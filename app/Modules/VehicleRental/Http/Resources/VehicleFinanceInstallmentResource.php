<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;

/** @mixin VehicleFinanceInstallment */
final class VehicleFinanceInstallmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_version' => $this->row_version,
            'finance_agreement_id' => $this->finance_agreement_id,
            'installment_number' => $this->installment_number,
            'due_date' => $this->due_date->toDateString(),
            'principal_due' => (string) $this->principal_due,
            'interest_due' => (string) $this->interest_due,
            'fee_due' => (string) $this->fee_due,
            'tax_due' => (string) $this->tax_due,
            'total_due' => (string) $this->total_due,
            'paid_amount' => (string) $this->paid_amount,
            'balance_due' => (string) $this->balance_due,
            'status' => $this->status->value,
            'invoice' => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'status' => $this->invoice->status->value,
            ] : null,
        ];
    }
}
