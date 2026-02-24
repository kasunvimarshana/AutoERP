<?php

namespace Modules\Maintenance\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id'  => ['required', 'uuid'],
            'requested_by'  => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string'],
            'priority'      => ['nullable', 'in:low,medium,high,critical'],
        ];
    }
}
