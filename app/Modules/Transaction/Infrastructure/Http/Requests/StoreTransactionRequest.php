<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;
use Modules\Transaction\Domain\ValueObjects\TransactionType;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'reference_number' => 'required|string|max:100',
            'transaction_type' => 'required|string|in:'.implode(',', TransactionType::values()),
            'amount'           => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'status'           => 'sometimes|string|in:'.implode(',', TransactionStatus::values()),
            'currency_code'    => 'sometimes|string|size:3',
            'exchange_rate'    => 'sometimes|numeric|min:0',
            'description'      => 'nullable|string',
            'reference_type'   => 'nullable|string|max:100',
            'reference_id'     => 'nullable|integer',
            'metadata'         => 'nullable|array',
        ];
    }
}
