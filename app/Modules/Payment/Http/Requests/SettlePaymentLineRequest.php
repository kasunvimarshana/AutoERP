<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class SettlePaymentLineRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function settlementStatus(): string
    {
        return (string) $this->input('status');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function metadata(): ?array
    {
        $metadata = $this->input('metadata');

        return is_array($metadata) ? $metadata : null;
    }
}
