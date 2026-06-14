<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalExpenseResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'usage_log_id' => $this->usage_log_id,
            'expense_type' => $this->enum($this->expense_type),
            'amount' => (string) $this->amount,
            'is_billable' => (bool) $this->is_billable,
            'receipt_no' => $this->receipt_no,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'attachments' => $this->attachments ?? [],
            'status' => $this->enum($this->status),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
        ];
    }
}
