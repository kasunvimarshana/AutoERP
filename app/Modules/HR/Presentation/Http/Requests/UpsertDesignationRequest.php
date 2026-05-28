<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertDesignationRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'department_id' => ['nullable', 'integer', 'min:1', 'exists:departments,id'],
            'designation_name' => array_merge($required, ['string', 'max:160']),
            'designation_code' => array_merge($required, ['string', 'max:50']),
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
