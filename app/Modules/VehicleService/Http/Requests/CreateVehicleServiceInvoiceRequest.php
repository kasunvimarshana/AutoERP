<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class CreateVehicleServiceInvoiceRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'line_quantities' => ['nullable', 'array'],
            'line_quantities.*' => ['decimal:0,6', 'gt:0'],
        ];
    }

    /** @return array<int, string> */
    public function lineQuantities(): array
    {
        $result = [];
        foreach ($this->input('line_quantities', []) as $id => $quantity) {
            $result[(int) $id] = (string) $quantity;
        }

        return $result;
    }
}
