<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class CreateVehicleServiceInvoiceRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules($this->routeIs('api.v1.vehicle-service.invoices.store')),
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
