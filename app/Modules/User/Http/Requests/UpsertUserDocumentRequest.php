<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertUserDocumentRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
            'user_id' => array_merge($required, ['integer', 'min:1', $this->tenantExists('users', 'id')]),
            'name' => array_merge($required, ['string', 'max:255']),
            'file_path' => array_merge($required, ['string', 'max:500']),
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
