<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalFinancialSide;

final class CalculateRentalRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_agreement_version' => ['required', 'integer', 'min:1'],
            'financial_side' => ['required', Rule::enum(RentalFinancialSide::class)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }
}
