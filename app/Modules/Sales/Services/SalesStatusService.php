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
                'approved' => ['partially_allocated', 'allocated', 'closed', 'cancelled'],
                'partially_allocated' => ['allocated', 'partially_delivered', 'closed'],
                'allocated' => ['partially_delivered', 'delivered', 'closed'],
                'partially_delivered' => ['delivered', 'partially_invoiced', 'partially_returned', 'closed'],
                'delivered' => ['partially_invoiced', 'invoiced', 'partially_returned', 'returned', 'closed'],
                'partially_invoiced' => ['invoiced', 'partially_returned', 'returned', 'closed'],
                'invoiced' => ['partially_returned', 'returned', 'closed'],
                'partially_returned' => ['returned', 'closed'],
            ],
            $to instanceof SalesDeliveryStatus => [
                'draft' => ['posted', 'cancelled'],
                'posted' => ['partially_returned', 'returned', 'partially_invoiced', 'invoiced', 'reversed'],
                'partially_returned' => ['returned', 'partially_invoiced', 'invoiced'],
                'partially_invoiced' => ['invoiced', 'partially_returned', 'returned'],
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
