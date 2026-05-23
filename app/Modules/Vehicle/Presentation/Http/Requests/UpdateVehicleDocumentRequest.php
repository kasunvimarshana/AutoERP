<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicle_documents', 'name')
                    ->where('tenant_id', $this->route('tenant'))
                    ->where('vehicle_id', $this->route('vehicle'))
                    ->ignore($this->route('document')),
            ],
            'file_path' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
