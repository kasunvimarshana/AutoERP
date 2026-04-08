<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'      => 'required|integer',
            'transaction_id' => 'required|integer',
            'account_code'   => 'required|string|max:20',
            'account_name'   => 'required|string|max:255',
            'debit_amount'   => 'sometimes|numeric|min:0',
            'credit_amount'  => 'sometimes|numeric|min:0',
            'description'    => 'nullable|string',
            'status'         => 'sometimes|string|in:'.implode(',', TransactionStatus::values()),
            'metadata'       => 'nullable|array',
        ];
    }
}
