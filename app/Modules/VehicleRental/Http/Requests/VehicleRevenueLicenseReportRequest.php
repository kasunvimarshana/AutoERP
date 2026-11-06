<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class VehicleRevenueLicenseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['start_date', 'end_date', 'status', 'vehicle_id'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:all,expired,expiring_soon,active'],
            'vehicle_id' => ['nullable', 'integer'],
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
