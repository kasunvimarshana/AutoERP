<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Invoice\Http\Resources\Concerns\FormatsInvoiceResources;

final class InvoiceBalanceResource extends ModuleResource
{
    use FormatsInvoiceResources;

    public function toArray(Request $request): array
    {
        return [
            'invoice_total' => (string) $this->invoice_total,
            'paid_amount' => (string) $this->paid_amount,
            'credit_allocated_amount' => (string) $this->credit_allocated_amount,
            'debit_allocated_amount' => (string) $this->debit_allocated_amount,
            'refunded_amount' => (string) $this->refunded_amount,
            'remaining_amount' => (string) $this->remaining_amount,
            'status' => $this->enumValue($this->status),
        ];
    }
}
