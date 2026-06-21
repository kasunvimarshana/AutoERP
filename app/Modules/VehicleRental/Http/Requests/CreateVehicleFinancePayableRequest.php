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
            'invoice_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
        ];
    }
}
