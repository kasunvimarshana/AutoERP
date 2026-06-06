<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Models\PaymentMethod;

final class PaymentMethodService
{
    public function assertUsable(?PaymentMethod $method, PaymentDirection $direction, ?string $referenceNumber): void
    {
        if (! $method instanceof PaymentMethod) {
            return;
        }

        if (! (bool) $method->is_active) {
            throw new InvalidArgumentException('Payment method is inactive.');
        }

        $allowed = $method->direction_allowed instanceof PaymentMethodDirection
            ? $method->direction_allowed
            : PaymentMethodDirection::from((string) $method->direction_allowed);

        if ($allowed !== PaymentMethodDirection::Both && $allowed->value !== $direction->value) {
            throw new InvalidArgumentException('Payment method is not allowed for this payment direction.');
        }

        if ((bool) $method->requires_reference && trim((string) $referenceNumber) === '') {
            throw new InvalidArgumentException('Payment method requires a reference number.');
        }
    }
}
