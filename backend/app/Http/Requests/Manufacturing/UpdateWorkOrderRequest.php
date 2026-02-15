<?php

declare(strict_types=1);

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add policy check: $this->user()->can('update', $workOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $workOrderId = $this->route('id');
        
        return [
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'bom_id' => ['nullable', 'integer', 'exists:bill_of_materials,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'work_order_number' => ['sometimes', 'string', 'max:50', "unique:work_orders,work_order_number,{$workOrderId}"],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,planned,released,in_progress,completed,cancelled'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'planned_quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'produced_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'scrap_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'planned_start_date' => ['sometimes', 'required', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'overhead_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'notes' => ['nullable', 'string'],
            'production_instructions' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            
            // Work Order Items
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.planned_quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'items.*.allocated_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'items.*.consumed_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'items.*.scrap_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.sequence' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'Selected product does not exist',
            'planned_end_date.after_or_equal' => 'Planned end date must be after or equal to start date',
            'items.*.product_id.required' => 'Material product is required',
            'items.*.product_id.exists' => 'Selected material product does not exist',
            'items.*.planned_quantity.required' => 'Material quantity is required',
        ];
    }
}
