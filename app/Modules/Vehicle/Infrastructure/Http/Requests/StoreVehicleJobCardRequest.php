<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleJobCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'assigned_mechanic_id' => 'nullable|integer|exists:users,id',
            'job_card_no' => 'required|string|max:120',
            'workflow_status' => 'nullable|in:draft,scheduled,in_progress,awaiting_parts,quality_check,completed,cancelled',
            'service_type' => 'nullable|in:maintenance,repair,inspection,accident,other',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'tasks' => 'nullable|array',
            'tasks.*.task_name' => 'required_with:tasks|string|max:255',
            'tasks.*.task_status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'tasks.*.estimated_hours' => 'nullable|numeric|min:0',
            'tasks.*.actual_hours' => 'nullable|numeric|min:0',
            'tasks.*.labor_rate' => 'nullable|numeric|min:0',
            'tasks.*.labor_cost' => 'nullable|numeric|min:0',
            'tasks.*.notes' => 'nullable|string',
            'parts' => 'nullable|array',
            'parts.*.service_task_id' => 'nullable|integer',
            'parts.*.product_id' => 'nullable|integer|exists:products,id',
            'parts.*.uom_id' => 'nullable|integer|exists:units_of_measure,id',
            'parts.*.quantity' => 'nullable|numeric|min:0',
            'parts.*.unit_cost' => 'nullable|numeric|min:0',
            'parts.*.line_total' => 'nullable|numeric|min:0',
            'parts.*.stock_movement_id' => 'nullable|integer',
            'parts.*.description' => 'nullable|string|max:255',
            'labor_cost_total' => 'nullable|numeric|min:0',
            'parts_cost_total' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'metadata' => 'nullable|array',
        ];
    }
}
