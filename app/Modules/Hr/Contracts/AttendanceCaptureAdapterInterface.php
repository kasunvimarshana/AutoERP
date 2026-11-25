<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Contracts;

use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Models\HrAttendancePunch;

interface AttendanceCaptureAdapterInterface
{
    public function method(): CaptureMethod;

    public function supports(CaptureMethod $method): bool;

    /**
     * Validate channel-specific payload, resolve employee/credential/geofence/device,
     * and record an idempotent, duplicate-safe attendance punch.
     *
     * @param  array<string, mixed>  $payload
     */
    public function capture(int $tenantId, array $payload): HrAttendancePunch;
}
