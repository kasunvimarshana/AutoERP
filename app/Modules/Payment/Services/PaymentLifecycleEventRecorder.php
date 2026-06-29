<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use BackedEnum;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Models\Payment;

final class PaymentLifecycleEventRecorder
{
    public function record(
        Payment $payment,
        PaymentLifecycleDimension $dimension,
        BackedEnum|string|null $from,
        BackedEnum|string $to,
        ?int $actorId = null,
        ?string $reason = null,
        ?array $metadata = null,
    ): void {
        $payment->lifecycleEvents()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'dimension' => $dimension->value,
            'from_state' => $this->value($from),
            'to_state' => $this->value($to),
            'reason' => $reason,
            'changed_by' => $actorId,
            'occurred_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function value(BackedEnum|string|null $state): ?string
    {
        return $state instanceof BackedEnum ? (string) $state->value : $state;
    }
}
