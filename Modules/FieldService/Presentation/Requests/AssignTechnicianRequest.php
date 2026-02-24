<?php

namespace Modules\FieldService\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'technician_id' => 'required|string|uuid',
        ];
    }
}
