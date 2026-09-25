<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hr\Services\EmployeeQueryService;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RunningChartAction;

final class DriverDirectory
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly EmployeeQueryService $employees,
    ) {}

    public function list(AgreementContext $context, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, true);

        return $this->employees->lookup(
            ['search' => $search],
            $context->tenantId,
            $context->organizationUnitId,
            $perPage,
            'available',
        );
    }
}
