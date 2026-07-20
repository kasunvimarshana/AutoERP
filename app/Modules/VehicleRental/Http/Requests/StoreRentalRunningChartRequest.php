<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class StoreRentalRunningChartRequest extends RentalRunningChartMutationRequest
{
    public function rules(): array
    {
        return $this->runningChartRules(true);
    }
}
