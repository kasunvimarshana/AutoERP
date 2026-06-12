<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Models\PaymentMethod;

final class PaymentMethodService
{
    public function assertUsable(
        ?PaymentMethod $method,
        PaymentDirection|string $direction,
        ?string $referenceNumber,
        ?int $tenantId = null,
        ?int $organizationUnitId = null,
        ?int $bankAccountId = null,
    ): void
    {
        if (! $method instanceof PaymentMethod) {
            return;
        }

        $direction = $direction instanceof PaymentDirection
            ? $direction
            : PaymentDirection::from((string) $direction);

        if ($method->tenant_id !== null && $tenantId !== null && (int) $method->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Payment method tenant must match payment tenant.');
        }

        if ($method->organization_unit_id !== null
            && (int) $method->organization_unit_id !== (int) $organizationUnitId) {
            throw new InvalidArgumentException('Payment method organization unit must match payment organization unit.');
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

        if ((bool) $method->requires_bank_account && ($bankAccountId === null || $bankAccountId < 1)) {
            throw new InvalidArgumentException('Payment method requires a bank account.');
        }
    }
}
