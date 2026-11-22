<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Models\AgreementAdjustment;

/** @mixin AgreementAdjustment */
final class AgreementAdjustmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_version' => (int) $this->row_version,
            'reference' => $this->reference,
            'customer_agreement_id' => $this->customer_agreement_id,
            'owner_agreement_id' => $this->owner_agreement_id,
            'invoice_id' => $this->invoice_id,
            'adjustment_type' => $this->adjustment_type->value,
            'adjustment_type_label' => $this->adjustment_type->label(),
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'status' => $this->status->value,
            'amount' => (string) $this->amount,
            'original_amount' => (string) $this->original_amount,
            'tax_amount' => (string) $this->tax_amount,
            'reason' => $this->reason,
            'customer_agreement' => $this->customerAgreement ? [
                'id' => $this->customerAgreement->id,
                'reference' => $this->customerAgreement->reference,
                'party_name' => $this->customerAgreement->party_name_snapshot,
            ] : null,
            'owner_agreement' => $this->ownerAgreement ? [
                'id' => $this->ownerAgreement->id,
                'reference' => $this->ownerAgreement->reference,
                'party_name' => $this->ownerAgreement->party_name_snapshot,
            ] : null,
            'invoice' => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'status' => $this->invoice->status->value,
            ] : null,
            'authorized_by' => $this->authorizedBy ? [
                'id' => $this->authorizedBy->id,
                'name' => $this->authorizedBy->name,
            ] : null,
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'history' => $this->whenLoaded('history', fn () => $this->history->map(fn ($h) => [
                'id' => $h->id,
                'from_status' => $h->from_status?->value ?? (string) $h->from_status,
                'to_status' => $h->to_status?->value ?? (string) $h->to_status,
                'reason' => $h->reason,
                'user' => $h->user ? ['id' => $h->user->id, 'name' => $h->user->name] : null,
                'created_at' => $h->created_at?->toIso8601String(),
            ])),
        ];
    }
}
