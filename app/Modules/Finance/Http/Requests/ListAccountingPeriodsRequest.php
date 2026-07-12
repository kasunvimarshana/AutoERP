<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\Enums\AccountingPeriodStatus;

final class ListAccountingPeriodsRequest extends TenantScopedRequest
{
    private const DEFAULT_PER_PAGE = 25;

    private const MAX_PER_PAGE = 100;

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(AccountingPeriodStatus::class)],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
