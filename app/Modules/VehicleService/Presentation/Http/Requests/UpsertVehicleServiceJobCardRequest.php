<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVehicleServiceJobCardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->input('tenant_id', $this->attributes->get('current_tenant_id')),
            'organization_unit_id' => $this->input(
                'organization_unit_id',
                $this->attributes->get('current_organization_unit_id'),
            ),
            'created_by' => $this->input('created_by', $this->attributes->get('current_user_id')),
            'updated_by' => $this->input('updated_by', $this->attributes->get('current_user_id')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'job_card_number' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'service_type_id' => ['nullable', 'integer', 'min:1', 'exists:vehicle_service_types,id'],
            'customer_id' => ['nullable', 'integer', 'min:1', 'exists:customers,id'],
            'vehicle_id' => ['nullable', 'integer', 'min:1', 'exists:vehicles,id'],
            'warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'status' => ['nullable', 'in:open,in_progress,waiting_parts,completed,invoiced,cancelled'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric'],
            'reported_issue' => ['nullable', 'string'],
            'resolution_notes' => ['nullable', 'string'],
            'start_datetime' => ['nullable', 'date'],
            'completed_datetime' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
            'promised_delivery_date_time' => ['nullable', 'date'],
            'warranty_eligible' => ['nullable', 'boolean'],
            'price_list_id' => ['nullable', 'integer', 'min:1', 'exists:price_lists,id'],
            'start_odometer' => ['nullable', 'integer', 'min:0'],
            'end_odometer' => ['nullable', 'integer', 'min:0'],
            'next_service_odometer' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:employees,id'],
            'header_discount_type' => ['nullable', 'in:percentage,fixed'],
            'header_discount_value' => ['nullable', 'numeric', 'min:0'],
            'header_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1'],
            'updated_by' => ['nullable', 'integer', 'min:1'],
            'inventory_lines' => ['nullable', 'array'],
            'inventory_lines.*.item_id' => ['required_with:inventory_lines', 'integer', 'min:1', 'exists:items,id'],
            'inventory_lines.*.variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'inventory_lines.*.batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'inventory_lines.*.serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'inventory_lines.*.warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'inventory_lines.*.location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'inventory_lines.*.description' => ['nullable', 'string'],
            'inventory_lines.*.uom_id' => ['required_with:inventory_lines', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'inventory_lines.*.quantity' => ['required_with:inventory_lines', 'numeric', 'gt:0'],
            'inventory_lines.*.unit_price' => ['required_with:inventory_lines', 'numeric', 'min:0'],
            'inventory_lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'inventory_lines.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'inventory_lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'inventory_lines.*.tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'inventory_lines.*.account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'labor_items' => ['nullable', 'array'],
            'labor_items.*.item_id' => ['required_with:labor_items', 'integer', 'min:1', 'exists:items,id'],
            'labor_items.*.combo_item_id' => ['nullable', 'integer', 'min:1', 'exists:combo_items,id'],
            'labor_items.*.description' => ['nullable', 'string'],
            'labor_items.*.uom_id' => ['required_with:labor_items', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'labor_items.*.quantity' => ['required_with:labor_items', 'numeric', 'gt:0'],
            'labor_items.*.unit_price' => ['required_with:labor_items', 'numeric', 'min:0'],
            'labor_items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_items.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'labor_items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'labor_items.*.tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'labor_items.*.incentive_type' => ['nullable', 'in:percentage,fixed'],
            'labor_items.*.incentive_value' => ['nullable', 'numeric', 'min:0'],
            'labor_items.*.account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'non_inventory_items' => ['nullable', 'array'],
            'non_inventory_items.*.name' => ['required_with:non_inventory_items', 'string', 'max:255'],
            'non_inventory_items.*.description' => ['nullable', 'string'],
            'non_inventory_items.*.uom_id' => ['required_with:non_inventory_items', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'non_inventory_items.*.quantity' => ['required_with:non_inventory_items', 'numeric', 'gt:0'],
            'non_inventory_items.*.unit_price' => ['required_with:non_inventory_items', 'numeric', 'min:0'],
            'non_inventory_items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'non_inventory_items.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'non_inventory_items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'non_inventory_items.*.tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'non_inventory_items.*.account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'labor_assignments' => ['nullable', 'array'],
            'labor_assignments.*.labor_item_id' => ['required_with:labor_assignments', 'integer', 'min:1'],
            'labor_assignments.*.employee_id' => ['required_with:labor_assignments', 'integer', 'min:1', 'exists:employees,id'],
            'labor_assignments.*.hours_worked' => ['nullable', 'numeric', 'min:0'],
            'labor_assignments.*.hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'labor_assignments.*.incentive_type' => ['nullable', 'in:percentage,fixed'],
            'labor_assignments.*.incentive_value' => ['nullable', 'numeric', 'min:0'],
            'labor_assignments.*.role' => ['nullable', 'string', 'max:255'],
            'labor_assignments.*.notes' => ['nullable', 'string'],
        ];
    }
}
