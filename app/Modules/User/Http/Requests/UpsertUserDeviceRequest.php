<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertUserDeviceRequest extends TenantScopedRequest
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
            'device_token' => array_merge($required, ['string', 'max:255']),
            'platform' => ['nullable', 'string', 'max:50'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'last_active_at' => ['nullable', 'date'],
        ];
    }
}
