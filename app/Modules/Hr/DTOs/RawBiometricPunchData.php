<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;

final readonly class RawBiometricPunchData
{
    public function __construct(
        public string $biometricUserId,
        public string $punchTime,
        public BiometricPunchState $punchState = BiometricPunchState::Auto,
        public BiometricVerifyMethod $verifyMethod = BiometricVerifyMethod::Unknown,
        public ?string $cardNumber = null,
        public ?string $workCode = null,
        public ?string $rawPayload = null,
    ) {}

    public function generateFingerprint(int $tenantId, int $deviceId): string
    {
        return hash('sha256', sprintf(
            '%d:%d:%s:%s:%s',
            $tenantId,
            $deviceId,
            $this->biometricUserId,
            $this->punchTime,
            $this->punchState->value,
        ));
    }
}
