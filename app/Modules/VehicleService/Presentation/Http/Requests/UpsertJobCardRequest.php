<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertJobCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();
        $jobId = $this->route('jobCard');
        $lineRules = [
            '*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            '*.uom_id' => ['required', 'integer', Rule::exists('unit_of_measures', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            '*.quantity' => ['required', 'numeric', 'gt:0'],
            '*.unit_price' => ['required', 'numeric', 'min:0'],
            '*.unit_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            '*.description' => ['sometimes', 'nullable', 'string'],
            '*.discount_type' => ['sometimes', 'nullable', Rule::in(['percentage', 'fixed'])],
            '*.discount_value' => ['sometimes', 'numeric', 'min:0'],
            '*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            '*.tax_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('tax_groups', 'id')->where('tenant_id', $tenantId)],
            '*.tax_amount' => ['sometimes', 'numeric', 'min:0'],
        ];

        return [
            'organization_unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId)],
            'job_card_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('vehicle_service_job_cards', 'job_card_number')->where('tenant_id', $tenantId)->ignore($jobId)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'service_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists('vehicle_service_types', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'linked_customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'reported_issue' => ['sometimes', 'nullable', 'string'],
            'resolution_notes' => ['sometimes', 'nullable', 'string'],
            'technician_notes' => ['sometimes', 'nullable', 'string'],
            'start_datetime' => ['sometimes', 'nullable', 'date'],
            'promised_delivery_date_time' => ['sometimes', 'nullable', 'date'],
            'estimated_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_odometer' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'end_odometer' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'next_service_odometer' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'header_discount_type' => ['sometimes', 'nullable', Rule::in(['percentage', 'fixed'])],
            'header_discount_value' => ['sometimes', 'numeric', 'min:0'],
            'header_discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'header_tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'header_charge_amount' => ['sometimes', 'numeric', 'min:0'],
            'header_adjustment_amount' => ['sometimes', 'numeric', 'min:0'],
            'header_adjustment_effect' => ['sometimes', Rule::in(['add', 'deduct'])],
            'notes' => ['sometimes', 'nullable', 'string'],
            'parts' => ['sometimes', 'array'],
            'labor_items' => ['sometimes', 'array'],
            'non_inventory_items' => ['sometimes', 'array'],
            ...$this->prefixedRules('parts', $lineRules),
            ...$this->prefixedRules('labor_items', $lineRules),
            'parts.*.warehouse_id' => ['sometimes', 'nullable', 'integer', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'parts.*.location_id' => ['sometimes', 'nullable', 'integer', Rule::exists('warehouse_locations', 'id')->where('tenant_id', $tenantId)],
            'non_inventory_items.*.name' => ['required', 'string', 'max:255'],
            'non_inventory_items.*.uom_id' => ['required', 'integer', Rule::exists('unit_of_measures', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'non_inventory_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'non_inventory_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'non_inventory_items.*.unit_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'non_inventory_items.*.description' => ['sometimes', 'nullable', 'string'],
            'non_inventory_items.*.discount_type' => ['sometimes', 'nullable', Rule::in(['percentage', 'fixed'])],
            'non_inventory_items.*.discount_value' => ['sometimes', 'numeric', 'min:0'],
            'non_inventory_items.*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'non_inventory_items.*.tax_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('tax_groups', 'id')->where('tenant_id', $tenantId)],
            'non_inventory_items.*.tax_amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    /** @param array<string, array<int, mixed>> $rules @return array<string, array<int, mixed>> */
    private function prefixedRules(string $prefix, array $rules): array
    {
        $prefixed = [];
        foreach ($rules as $key => $rule) {
            $prefixed[$prefix.'.'.$key] = $rule;
        }

        return $prefixed;
    }
}
