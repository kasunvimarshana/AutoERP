<?php

namespace Modules\QualityControl\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityAlertRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'inspection_id' => 'nullable|string|uuid',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'product_id'    => 'nullable|string|uuid',
            'lot_number'    => 'nullable|string|max:100',
            'priority'      => 'nullable|in:low,medium,high,critical',
            'assigned_to'   => 'nullable|string|uuid',
            'deadline'      => 'nullable|date',
        ];
    }
}
