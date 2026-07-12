<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class InvoiceActionRequest extends TenantScopedRequest
{
    private const REASON_MAX_LENGTH = 1000;

    public function rules(): array
    {
        $mutation = ! $this->isMethod('get');
        $cancellation = $this->routeIs('api.v1.invoices.cancel');
        $reversal = $this->routeIs('api.v1.invoices.reverse');

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $mutation
                ? ['required', 'integer', 'min:1']
                : ['prohibited'],
            'reversal_date' => $reversal
                ? ['required', 'date_format:Y-m-d']
                : ['prohibited'],
            'reason' => ($cancellation || $reversal)
                ? ['required', 'string', 'max:'.self::REASON_MAX_LENGTH]
                : ['prohibited'],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }

    public function reason(): ?string
    {
        if (! $this->filled('reason')) {
            return null;
        }

        $reason = trim((string) $this->input('reason'));

        return $reason === '' ? null : $reason;
    }

    public function reversalDate(): string
    {
        return (string) $this->input('reversal_date');
    }
}
