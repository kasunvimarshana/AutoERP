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
