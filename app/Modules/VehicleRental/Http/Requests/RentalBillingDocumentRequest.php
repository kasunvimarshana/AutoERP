<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\VehicleRental\Data\AgreementContext;

final class RentalBillingDocumentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            return [
                'expected_version' => ['required', 'integer', 'min:1'],
                'due_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ];
        }

        if ($this->isMethod('POST') && str_ends_with($this->path(), 'cancel')) {
            return [
                'expected_version' => ['required', 'integer', 'min:1'],
                'reason' => ['required', 'string', 'min:5', 'max:1000'],
            ];
        }

        if ($this->isMethod('POST') && str_ends_with($this->path(), 'quotation-preview')) {
            return [
                'billing_cycle_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'customer_agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'period_from' => ['sometimes', 'nullable', 'date'],
                'period_until' => ['sometimes', 'nullable', 'date'],
                'custody_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'base_rent_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'excess_km' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'excess_km_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'normal_ot_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'normal_ot_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'double_ot_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'double_ot_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'triple_ot_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'triple_ot_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'night_outs' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'night_out_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'tolls_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'additions' => ['sometimes', 'array'],
                'additions.*.description' => ['required_with:additions', 'string', 'max:255'],
                'additions.*.amount' => ['required_with:additions', 'numeric', 'min:0'],
                'additions.*.category' => ['sometimes', 'nullable', 'string', 'max:50'],
                'deductions' => ['sometimes', 'array'],
                'deductions.*.description' => ['required_with:deductions', 'string', 'max:255'],
                'deductions.*.amount' => ['required_with:deductions', 'numeric', 'min:0'],
                'deductions.*.category' => ['sometimes', 'nullable', 'string', 'max:50'],
                'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            ];
        }

        if ($this->isMethod('POST') && str_ends_with($this->path(), 'owner-bill-preview')) {
            return [
                'owner_agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'start_date' => ['sometimes', 'nullable', 'date'],
                'end_date' => ['sometimes', 'nullable', 'date'],
                'period_from' => ['sometimes', 'nullable', 'date'],
                'period_until' => ['sometimes', 'nullable', 'date'],
                'wht_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
                'include_maintenance_chargebacks' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'direction' => ['sometimes', 'nullable', 'string', Rule::enum(InvoiceDirection::class)],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', 'string', Rule::enum(InvoiceStatus::class)],
            'balance_status' => ['sometimes', 'nullable', 'string', 'in:unpaid,partially_paid,paid,overdue'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'party_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before using Rental.']]);
        }

        return new AgreementContext($this->tenantId(), $this->organizationUnitId(), $this->currentUserId());
    }

    public function expectedVersion(): int
    {
        if (! $this->filled('expected_version')) {
            throw ValidationException::withMessages(['expected_version' => ['Reload the record and supply its current version.']]);
        }

        return (int) $this->input('expected_version');
    }
}
