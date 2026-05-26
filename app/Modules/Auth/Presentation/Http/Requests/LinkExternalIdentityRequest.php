<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LinkExternalIdentityRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'provider_key' => ['required', 'string', 'max:120'],
            'provider_user_key' => ['required', 'string', 'max:190'],
            'is_primary' => ['nullable', 'boolean'],
            'claims' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
