<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertOrganizationUnitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->isMethod('post') ? ['required', 'integer', 'min:1'] : ['sometimes', 'integer', 'min:1'],
            'name' => array_merge($required, ['string', 'max:255']),
            'level' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
