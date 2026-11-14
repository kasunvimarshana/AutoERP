<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\BillingCycleItemCategory;

final class BillingCycleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $requiresVersion = $this->isMethod('POST') && ! str_ends_with($this->path(), 'billing-cycles') && ! str_ends_with($this->path(), 'preview');

        return [
            'expected_version' => $requiresVersion
                ? ['required', 'integer', 'min:1']
                : ['sometimes', 'required', 'integer', 'min:1'],
            'period_from' => ['sometimes', 'required', 'date'],
            'period_until' => ['sometimes', 'required', 'date', 'after_or_equal:period_from'],
            'billing_mode' => ['sometimes', 'string', 'in:without_running_chart,with_running_chart,monthly,weekly,daily,custom'],
            'finalization_mode' => ['sometimes', 'string', 'in:supplementary_invoice,consolidated_revision,supplementary,consolidated'],
            'chart_ids' => ['sometimes', 'array'],
            'chart_ids.*' => ['integer', 'min:1'],
            'item_type' => ['sometimes', 'string', 'in:addition,deduction'],
            'category' => ['sometimes', 'required', Rule::enum(BillingCycleItemCategory::class)],
            'quantity' => ['sometimes', 'numeric', 'gt:0'],
            'unit_rate' => ['sometimes', 'numeric'],
            'description' => ['sometimes', 'string', 'max:255'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'driver_employee_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'issue_initial_invoice' => ['sometimes', 'boolean'],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'exchange_rate' => ['sometimes', 'numeric', 'gt:0'],
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
