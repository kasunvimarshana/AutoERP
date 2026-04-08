<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Biometric;

use RuntimeException;

/**
 * Thrown when a biometric device operation fails (device unavailable,
 * scan error, enrollment failure, etc.).
 */
class BiometricDeviceException extends RuntimeException {}
