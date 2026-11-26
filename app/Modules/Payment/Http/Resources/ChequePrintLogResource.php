<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\Models\ChequePrintLog;

/**
 * @mixin ChequePrintLog
 */
final class ChequePrintLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'tenant_id' => (int) $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id !== null ? (int) $this->organization_unit_id : null,
            'payment_id' => (int) $this->payment_id,
            'payment_number' => $this->relationLoaded('payment') && $this->payment ? (string) $this->payment->payment_number : null,
            'payment_line_id' => $this->payment_line_id !== null ? (int) $this->payment_line_id : null,
            'cheque_template_id' => (int) $this->cheque_template_id,
            'cheque_template_name' => $this->relationLoaded('chequeTemplate') && $this->chequeTemplate ? (string) $this->chequeTemplate->template_name : null,
            'printed_by' => $this->printed_by !== null ? (int) $this->printed_by : null,
            'printed_by_name' => $this->relationLoaded('printedBy') && $this->printedBy ? (string) ($this->printedBy->name ?? $this->printedBy->email ?? '') : null,
            'printed_at' => $this->printed_at?->toISOString(),
            'print_status' => $this->print_status->value ?? (string) $this->print_status,
            'notes' => $this->notes !== null ? (string) $this->notes : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
