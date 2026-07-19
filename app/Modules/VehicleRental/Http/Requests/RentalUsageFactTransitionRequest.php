<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;

final class RentalUsageFactTransitionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::enum(RentalUsageFactStatus::class)],
            'reason' => ['nullable', 'string'],
        ];
    }
}
