<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceInvoiceLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $invoiceStatus = $this->enum($this->invoice?->status);

        $balanceDue = $this->invoice?->balance === null
            ? null
            : (string) $this->invoice->balance->remaining_amount;
        $active = ! in_array($invoiceStatus, ['cancelled', 'void'], true) && $this->status === 'active';

        return [
            'id' => (int) $this->getKey(),
            'invoice_id' => (int) $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'invoice_total' => (string) $this->invoice_total,
            'balance_due' => $balanceDue,
            'invoice_status' => $invoiceStatus,
            'status' => $active ? 'active' : 'inactive',
            'can_receive_payment' => $active
                && in_array($invoiceStatus, ['posted', 'partially_paid'], true)
                && $this->isPositiveDecimal($balanceDue),
        ];
    }

    private function isPositiveDecimal(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = trim($value);
        if ($value === '' || str_starts_with($value, '-')) {
            return false;
        }

        return trim(str_replace('.', '', $value), '0') !== '';
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
