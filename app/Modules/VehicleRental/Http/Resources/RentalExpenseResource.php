<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalExpenseResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'usage_log_id' => $this->usage_log_id,
            'expense_type' => $this->enum($this->expense_type),
            'expense_date' => $this->expense_date?->toDateString(),
            'currency_id' => $this->currency_id,
            'amount' => (string) $this->amount,
            'tax_group_id' => $this->tax_group_id,
            'tax_amount' => (string) $this->tax_amount,
            'withholding_amount' => (string) $this->withholding_amount,
            'original_net_amount' => (string) $this->original_net_amount,
            'original_tax_group_id' => $this->original_tax_group_id,
            'original_tax_amount' => (string) $this->original_tax_amount,
            'original_gross_amount' => (string) $this->original_gross_amount,
            'original_withholding_amount' => (string) $this->original_withholding_amount,
            'recoverable_input_tax_amount' => (string) $this->recoverable_input_tax_amount,
            'recovery_base_amount' => (string) $this->recovery_base_amount,
            'recovery_tax_group_id' => $this->recovery_tax_group_id,
            'recovery_tax_amount' => (string) $this->recovery_tax_amount,
            'recovery_withholding_amount' => (string) $this->recovery_withholding_amount,
            'markup_amount' => (string) $this->markup_amount,
            'generated_charge_id' => $this->generated_charge_id,
            'financial_treatment' => $this->enum($this->financial_treatment),
            'is_billable' => (bool) $this->is_billable,
            'is_recoverable' => (bool) $this->is_recoverable,
            'is_reimbursable' => (bool) $this->is_reimbursable,
            'responsible_party_type' => $this->responsible_party_type,
            'responsible_party_id' => $this->responsible_party_id,
            'charge_generation_status' => $this->charge_generation_status,
            'receipt_no' => $this->receipt_no,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'attachments' => $this->attachments ?? [],
            'status' => $this->enum($this->status),
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at?->toISOString(),
        ];
    }
}
