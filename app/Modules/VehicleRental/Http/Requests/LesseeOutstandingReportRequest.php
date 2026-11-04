<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class LesseeOutstandingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'as_of_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'integer'],
            'agreement_id' => ['nullable', 'integer'],
            'aging_bucket' => ['nullable', 'string', 'in:all,0_30,31_60,61_90,90_plus'],
        ];
    }

    public function context(): AgreementContext
    {
        $tenantId = (int) ($this->user()?->tenant_id ?? 1);
        $orgUnitId = (int) ($this->user()?->organization_unit_id ?? 1);

        return new AgreementContext(
            tenantId: $tenantId,
            organizationUnitId: $orgUnitId,
            actorId: (int) ($this->user()?->id ?? 1),
        );
    }
}
