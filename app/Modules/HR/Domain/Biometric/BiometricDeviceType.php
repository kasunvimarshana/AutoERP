<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Biometric;

/**
 * Canonical device-type identifiers.
 *
 * Use these constants throughout the system so that device types are never
 * represented as magic strings.  Adding a new physical technology only requires
 * a new constant here plus a new BiometricDeviceInterface adapter – no other
 * code changes are necessary.
 */
final class BiometricDeviceType
{
    public const FINGERPRINT = 'fingerprint';

    public const FACE        = 'face';

    public const IRIS        = 'iris';

    public const RFID        = 'rfid';

    public const PALM_VEIN   = 'palm_vein';

    private function __construct() {}
}
