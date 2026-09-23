<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\Contracts\PaymentMethodProviderInterface;
use Modules\Payment\DTOs\PaymentMethodData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Models\PaymentMethod;

final class PaymentMethodProvider implements PaymentMethodProviderInterface
{
    public function __construct(private readonly PaymentMethodService $methods) {}

    public function outboundMethods(int $tenantId, int $organizationUnitId): array
    {
        return $this->methods
            ->effectiveActiveForDirection($tenantId, $organizationUnitId, PaymentDirection::Outbound)
            ->map(fn (PaymentMethod $method): PaymentMethodData => $this->toData($method))
            ->values()
            ->all();
    }

    public function resolveOutbound(
        int $paymentMethodId,
        int $tenantId,
        int $organizationUnitId,
        ?string $referenceNumber = null,
        bool $hasInstrumentDetails = false,
    ): PaymentMethodData {
        $method = PaymentMethod::query()->lockForUpdate()->find($paymentMethodId);
        $this->methods->assertUsable(
            $method,
            PaymentDirection::Outbound,
            $referenceNumber,
            $tenantId,
            $organizationUnitId,
            $hasInstrumentDetails,
        );

        return $this->toData($method);
    }

    private function toData(PaymentMethod $method): PaymentMethodData
    {
        return new PaymentMethodData(
            id: (int) $method->getKey(),
            code: (string) $method->code,
            name: (string) $method->name,
            type: $method->method_type instanceof \BackedEnum
                ? (string) $method->method_type->value
                : (string) $method->method_type,
            requiresReference: (bool) $method->requires_reference,
            requiresInstrumentDetails: (bool) $method->requires_instrument_details,
        );
    }
}
