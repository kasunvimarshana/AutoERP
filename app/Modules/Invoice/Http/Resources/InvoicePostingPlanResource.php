<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoicePostingPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'row_version' => (int) $this->row_version,
            'posting_profile_code' => $this->posting_profile_code instanceof BackedEnum
                ? $this->posting_profile_code->value
                : (string) $this->posting_profile_code,
            'posting_date' => $this->posting_date?->toDateString(),
            'description' => $this->description,
            'status' => $this->status instanceof BackedEnum
                ? $this->status->value
                : (string) $this->status,
            'finance_posting_reference' => $this->finance_posting_reference,
            'finance_reversal_reference' => $this->finance_reversal_reference,
            'posted_at' => $this->posted_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reason' => $this->reversal_reason,
        ];
    }
}
