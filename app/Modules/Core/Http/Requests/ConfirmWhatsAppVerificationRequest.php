<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

final class ConfirmWhatsAppVerificationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'regex:/^WA-[A-F0-9]{8}$/'],
            'ownership_confirmed' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    public function code(): string
    {
        return (string) $this->validated('code');
    }
}
