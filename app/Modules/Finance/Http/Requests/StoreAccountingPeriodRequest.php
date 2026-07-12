<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\DTOs\AccountingPeriodData;

final class StoreAccountingPeriodRequest extends TenantScopedRequest
{
    private const CODE_MAX_LENGTH = 50;

    private const NAME_MAX_LENGTH = 150;

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:'.self::CODE_MAX_LENGTH],
            'name' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function toData(): AccountingPeriodData
    {
        $data = $this->validated();

        return new AccountingPeriodData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            code: (string) $data['code'],
            name: (string) $data['name'],
            startDate: (string) $data['start_date'],
            endDate: (string) $data['end_date'],
            createdBy: $this->currentUserId(),
        );
    }
}
