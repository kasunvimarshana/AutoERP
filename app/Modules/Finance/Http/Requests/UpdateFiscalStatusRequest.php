<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\Enums\FiscalPeriodStatus;

final class UpdateFiscalStatusRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(FiscalPeriodStatus::class)],
        ];
    }

    public function status(): FiscalPeriodStatus
    {
        return FiscalPeriodStatus::from((string) $this->input('status'));
    }
}
