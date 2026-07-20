<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class UpdateRentalRunningChartRequest extends RentalRunningChartMutationRequest
{
    public function rules(): array
    {
        return [
            ...$this->runningChartRules(false),
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }
}
