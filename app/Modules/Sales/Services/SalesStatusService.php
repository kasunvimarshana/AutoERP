<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Enums\SalesQuotationStatus;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesStatusHistory;

final class SalesStatusService
{
    public function transition(Model $model, BackedEnum $to, ?int $changedBy = null, ?string $reason = null): void
    {
        $from = $model->getAttribute('status');
        $fromValue = $from instanceof BackedEnum ? (string) $from->value : (string) $from;
        $toValue = (string) $to->value;
        $this->assertAllowed($from, $to);

        $model->setAttribute('status', $to);
        $model->save();

        SalesStatusHistory::query()->create([
            'tenant_id' => $model->getAttribute('tenant_id'),
            'organization_unit_id' => $model->getAttribute('organization_unit_id'),
            'source_type' => class_basename($model),
            'source_id' => $model->getKey(),
            'from_status' => $fromValue !== '' ? $fromValue : null,
            'to_status' => $toValue,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    private function assertAllowed(mixed $from, BackedEnum $to): void
    {
        $fromValue = $from instanceof BackedEnum ? $from->value : (string) $from;
        $toValue = $to->value;
        $allowed = match (true) {
            $to instanceof SalesQuotationStatus => [
                'draft' => ['sent', 'accepted', 'cancelled'],
                'sent' => ['accepted', 'rejected', 'expired', 'cancelled'],
                'accepted' => ['converted', 'cancelled'],
            ],
            $to instanceof SalesOrderStatus => [
                'draft' => ['pending_approval', 'approved', 'cancelled'],
                'pending_approval' => ['approved', 'cancelled'],
                'approved' => ['closed', 'cancelled'],
            ],
            $to instanceof SalesDeliveryStatus => [
                'draft' => ['posted', 'cancelled'],
                'posted' => ['reversed'],
            ],
            $to instanceof SalesReturnStatus => [
                'draft' => ['approved', 'posted', 'cancelled'],
                'approved' => ['posted', 'cancelled'],
                'posted' => ['reversed'],
            ],
            default => [],
        };

        if ($fromValue === $toValue || in_array($toValue, $allowed[$fromValue] ?? [], true)) {
            return;
        }

        throw new InvalidArgumentException('Invalid sales status transition.');
    }
}
