<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Services\RentalUsageContextService;

final class RunningChartPreviewRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'mode' => ['required', Rule::in([
                RentalUsageContextService::MODE_LESSEE,
                RentalUsageContextService::MODE_LESSOR,
                RentalUsageContextService::MODE_LINKED,
            ])],
            'lessee_agreement_id' => ['required_if:mode,lessee,linked', 'integer', 'min:1'],
            'lessee_agreement_vehicle_id' => ['required_if:mode,lessee,linked', 'integer', 'min:1'],
            'lessor_agreement_id' => ['required_if:mode,lessor,linked', 'integer', 'min:1'],
            'lessor_agreement_vehicle_id' => ['required_if:mode,lessor,linked', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
            'trips' => ['array'],
            'trips.*.start_time' => ['required_with:trips.*.end_time', 'date_format:H:i'],
            'trips.*.end_time' => ['required_with:trips.*.start_time', 'date_format:H:i'],
            'trips.*.start_odometer' => ['required_with:trips', 'decimal:0,6', 'min:0'],
            'trips.*.end_odometer' => ['required_with:trips', 'decimal:0,6', 'gte:trips.*.start_odometer'],
            'trips.*.events' => ['array'],
            'trips.*.events.*.event_type' => ['required', Rule::enum(RentalUsageEventType::class)],
            'trips.*.events.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }

    public function mode(): string
    {
        return (string) $this->input('mode');
    }

    public function selectedAgreementId(): int
    {
        return $this->mode() === RentalUsageContextService::MODE_LESSOR
            ? (int) $this->input('lessor_agreement_id')
            : (int) $this->input('lessee_agreement_id');
    }

    public function selectedAgreementVehicleId(): int
    {
        return $this->mode() === RentalUsageContextService::MODE_LESSOR
            ? (int) $this->input('lessor_agreement_vehicle_id')
            : (int) $this->input('lessee_agreement_vehicle_id');
    }

    public function counterpartAgreementId(): ?int
    {
        return $this->mode() === RentalUsageContextService::MODE_LINKED
            ? (int) $this->input('lessor_agreement_id')
            : null;
    }

    public function counterpartAgreementVehicleId(): ?int
    {
        return $this->mode() === RentalUsageContextService::MODE_LINKED
            ? (int) $this->input('lessor_agreement_vehicle_id')
            : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function trips(): array
    {
        return array_map(function (array $trip): array {
            $trip['usage_date'] = (string) $this->input('usage_date');

            return $trip;
        }, $this->input('trips', []));
    }
}
