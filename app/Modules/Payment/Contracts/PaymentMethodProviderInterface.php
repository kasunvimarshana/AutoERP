<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Contracts;

use Modules\Payment\DTOs\PaymentMethodData;

interface PaymentMethodProviderInterface
{
    /** @return list<PaymentMethodData> */
    public function outboundMethods(int $tenantId, int $organizationUnitId): array;

    public function resolveOutbound(
        int $paymentMethodId,
        int $tenantId,
        int $organizationUnitId,
        ?string $referenceNumber = null,
        bool $hasInstrumentDetails = false,
    ): PaymentMethodData;
}
