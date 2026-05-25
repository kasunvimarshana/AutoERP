<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertOrganizationUnitSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->isMethod('post') ? ['required', 'integer', 'min:1'] : ['sometimes', 'integer', 'min:1'],
            'organization_unit_id' => array_merge($required, ['integer', 'min:1']),
            'group_id' => array_merge($required, ['integer', 'min:1']),
            'key' => array_merge($required, ['string', 'max:255']),
            'value' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}