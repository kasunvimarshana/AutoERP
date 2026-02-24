<?php

namespace Modules\Maintenance\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:200'],
            'serial_number'    => ['required', 'string', 'max:100'],
            'category'         => ['nullable', 'string', 'max:100'],
            'location'         => ['nullable', 'string', 'max:200'],
            'assigned_team_id' => ['nullable', 'uuid'],
            'purchase_date'    => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
