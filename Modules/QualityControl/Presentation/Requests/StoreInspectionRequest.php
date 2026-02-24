<?php

namespace Modules\QualityControl\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quality_point_id' => 'nullable|string|uuid',
            'product_id'       => 'nullable|string|uuid',
            'lot_number'       => 'nullable|string|max:100',
            'qty_inspected'    => 'nullable|numeric|min:0',
            'inspector_id'     => 'nullable|string|uuid',
            'notes'            => 'nullable|string',
        ];
    }
}
