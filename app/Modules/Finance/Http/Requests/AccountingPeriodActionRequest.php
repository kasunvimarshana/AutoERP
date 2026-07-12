<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class AccountingPeriodActionRequest extends TenantScopedRequest
{
    private const REASON_MAX_LENGTH = 1000;

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:'.self::REASON_MAX_LENGTH],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
