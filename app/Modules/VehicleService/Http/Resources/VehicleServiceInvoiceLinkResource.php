<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class VehicleServiceInvoiceLinkResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        $invoiceStatus = $this->enum($this->invoice?->status);

        return [
            'id' => (int) $this->getKey(),
            'invoice_id' => (int) $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'invoice_total' => (string) $this->invoice_total,
            'balance_due' => $this->invoice?->balance === null
                ? null
                : (string) $this->invoice->balance->remaining_amount,
            'invoice_status' => $invoiceStatus,
            'status' => in_array($invoiceStatus, ['cancelled', 'void'], true)
                ? 'inactive'
                : $this->status,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
