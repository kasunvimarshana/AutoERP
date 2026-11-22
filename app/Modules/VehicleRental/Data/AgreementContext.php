<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Data;

final readonly class AgreementContext
{
    public function __construct(public int $tenantId, public int $organizationUnitId, public int $actorId) {}
}
