<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;

/** @mixin VehicleFinanceAgreement */
final class VehicleFinanceAgreementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_version' => $this->row_version,
            'agreement_number' => $this->agreement_number,
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier_name_snapshot,
            ],
            'vehicle' => [
                'id' => $this->vehicle_id,
                'label' => $this->vehicle_label_snapshot,
                'make' => $this->vehicle?->make?->name,
                'model' => $this->vehicle?->model?->name,
            ],
            'agreement_date' => $this->agreement_date->toDateString(),
            'starts_at' => $this->starts_at->toIso8601String(),
            'matures_at' => $this->matures_at->toIso8601String(),
            'currency' => [
                'id' => $this->currency_id,
                'code' => $this->currency_code_snapshot,
            ],
            'principal_amount' => (string) $this->principal_amount,
            'initial_deposit_amount' => (string) $this->initial_deposit_amount,
            'residual_value' => (string) $this->residual_value,
            'interest_method' => $this->interest_method,
            'annual_interest_rate' => (string) $this->annual_interest_rate,
            'installment_frequency' => $this->installment_frequency,
            'installment_count' => $this->installment_count,
            'payment_term_days' => $this->payment_term_days,
            'tax_group_id' => $this->tax_group_id,
            'status' => $this->status->value,
            'remarks' => $this->remarks,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'installments' => $this->whenLoaded('installments', fn () => VehicleFinanceInstallmentResource::collection($this->installments)),
        ];
    }
}
