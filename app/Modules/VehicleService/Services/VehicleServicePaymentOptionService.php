<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServicePaymentOptionService
{
    public function __construct(private readonly PaymentMethodService $methods) {}

    /** @return array{methods: list<array<string, mixed>>} */
    public function options(VehicleServiceJob $job): array
    {
        $methods = $this->methods
            ->effectiveActiveForDirection(
                (int) $job->tenant_id,
                $job->organization_unit_id,
                PaymentDirection::Inbound,
            )
            ->map(fn (PaymentMethod $method): array => [
                'id' => (int) $method->getKey(),
                'code' => (string) $method->code,
                'name' => (string) $method->name,
                'method_type' => $method->method_type instanceof \BackedEnum
                    ? $method->method_type->value
                    : (string) $method->method_type,
                'requires_reference' => (bool) $method->requires_reference,
                'requires_instrument_details' => (bool) $method->requires_instrument_details,
            ])
            ->all();

        return ['methods' => $methods];
    }
}
