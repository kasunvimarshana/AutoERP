<?php

namespace Modules\Maintenance\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id'   => ['required', 'uuid'],
            'order_type'     => ['required', 'in:preventive,corrective'],
            'description'    => ['nullable', 'string'],
            'scheduled_date' => ['nullable', 'date'],
            'assigned_to'    => ['nullable', 'string', 'max:200'],
            'labor_cost'     => ['nullable', 'numeric', 'min:0'],
            'parts_cost'     => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
