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
            'expected_payment_version' => ['required', 'integer', 'min:1'],
            'expected_line_version' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['prohibited'],
        ];
    }

    public function settlementStatus(): string
    {
        return strtolower(trim((string) $this->input('status')));
    }

    public function expectedPaymentVersion(): int
    {
        return (int) $this->input('expected_payment_version');
    }

    public function expectedLineVersion(): int
    {
        return (int) $this->input('expected_line_version');
    }

    public function reason(): ?string
    {
        return $this->filled('reason') ? trim((string) $this->input('reason')) : null;
    }
}
