<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class AllocatePurchaseDebitNoteRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'invoice_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'expected_version' => ['required', 'integer', 'min:1'],
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

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }
}
