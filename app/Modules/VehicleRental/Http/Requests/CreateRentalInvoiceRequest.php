<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceStatus;

final class CreateRentalInvoiceRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'line_ids' => ['nullable', 'array', 'min:1'],
            'line_ids.*' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
