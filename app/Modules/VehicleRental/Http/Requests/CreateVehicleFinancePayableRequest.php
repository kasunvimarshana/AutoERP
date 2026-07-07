<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\Enums\InvoiceStatus;

final class CreateVehicleFinancePayableRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'invoice_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
        ];
    }
}
