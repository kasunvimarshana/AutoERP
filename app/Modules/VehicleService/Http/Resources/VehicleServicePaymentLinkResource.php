<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class VehicleServicePaymentLinkResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'payment_id' => (int) $this->payment_id,
            'payment_number' => $this->payment?->payment_number,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'allocated_amount' => (string) $this->allocated_amount,
            'status' => $this->status,
        ];
    }
}
