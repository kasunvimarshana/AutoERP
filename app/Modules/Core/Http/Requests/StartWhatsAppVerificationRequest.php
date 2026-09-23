<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

final class StartWhatsAppVerificationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function idempotencyKey(): string
    {
        return (string) $this->validated('idempotency_key');
    }
}
