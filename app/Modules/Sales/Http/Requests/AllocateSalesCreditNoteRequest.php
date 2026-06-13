<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

final class AllocateSalesCreditNoteRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'invoice_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
        ]);
    }

    public function invoiceId(): int
    {
        return (int) $this->input('invoice_id');
    }

    public function amount(): string
    {
        return (string) $this->input('amount');
    }
}
