<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaxTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'source_module' => $this->source_module,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source_number' => $this->source_number,
            'party_type' => $this->party_type,
            'party_id' => $this->party_id,
            'tax_code' => $this->tax_code,
            'tax_name' => $this->tax_name,
            'tax_type' => $this->tax_type,
            'taxable_amount' => (string) $this->taxable_amount,
            'tax_amount' => (string) $this->tax_amount,
            'withholding_amount' => (string) $this->withholding_amount,
            'is_withholding' => (bool) $this->is_withholding,
            'recoverable' => (bool) $this->recoverable,
            'payable' => (bool) $this->payable,
            'receivable' => (bool) $this->receivable,
            'account' => $this->whenLoaded('account', fn () => $this->account ? [
                'id' => $this->account->id,
                'code' => $this->account->code,
                'name' => $this->account->name,
            ] : null),
        ];
    }
}
