<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalCalculationSide;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;

final class ListRentalRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'kind' => ['nullable', Rule::enum(RentalAgreementKind::class)],
            'agreement_status' => ['nullable', Rule::enum(RentalAgreementStatus::class)],
            'assignment_side' => ['nullable', Rule::enum(RentalAssignmentSide::class)],
            'running_chart_status' => ['nullable', Rule::enum(RentalRunningChartStatus::class)],
            'calculation_side' => ['nullable', Rule::enum(RentalCalculationSide::class)],
            'calculation_status' => ['nullable', Rule::enum(RentalCalculationStatus::class)],
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'assignment_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
