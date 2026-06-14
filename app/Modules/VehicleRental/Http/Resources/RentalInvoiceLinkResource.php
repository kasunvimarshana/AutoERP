<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalInvoiceLinkResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        $invoiceStatus = $this->enum($this->invoice?->status);

        return [
            'id' => (int) $this->getKey(),
            'charge_id' => (int) $this->charge_id,
            'invoice_id' => (int) $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'invoiced_amount' => (string) $this->invoiced_amount,
            'balance_due' => $this->invoice?->balance === null ? null : (string) $this->invoice->balance->remaining_amount,
            'invoice_status' => $invoiceStatus,
            'status' => in_array($invoiceStatus, ['cancelled', 'void'], true) ? 'inactive' : $this->status,
        ];
    }
}
