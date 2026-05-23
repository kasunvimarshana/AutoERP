<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSequenceNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'document_type' => ['required', 'string', 'max:255'],
            'period_type' => ['nullable', 'string', 'in:yearly,monthly,infinite'],
            'at_date' => ['nullable', 'date'],
        ];
    }
}
