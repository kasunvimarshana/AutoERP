<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'document_type' => array_merge($required, ['string', 'max:255']),
            'prefix' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'padding' => ['nullable', 'integer', 'min:1', 'max:18'],
            'next_number' => ['nullable', 'integer', 'min:1'],
            'period_type' => ['nullable', 'string', 'in:yearly,monthly,infinite'],
            'period_value' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
