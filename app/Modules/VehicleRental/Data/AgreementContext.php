<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Data;

final readonly class AgreementContext
{
    public function __construct(public int $tenantId, public int $organizationUnitId, public int $actorId) {}
}
