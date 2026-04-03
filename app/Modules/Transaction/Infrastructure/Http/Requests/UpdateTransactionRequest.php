<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;
use Modules\Transaction\Domain\ValueObjects\TransactionType;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_type' => 'sometimes|required|string|in:'.implode(',', TransactionType::values()),
            'amount'           => 'sometimes|required|numeric|min:0',
            'transaction_date' => 'sometimes|required|date',
            'status'           => 'nullable|string|in:'.implode(',', TransactionStatus::values()),
            'currency_code'    => 'nullable|string|size:3',
            'exchange_rate'    => 'nullable|numeric|min:0',
            'description'      => 'nullable|string',
            'reference_type'   => 'nullable|string|max:100',
            'reference_id'     => 'nullable|integer',
            'metadata'         => 'nullable|array',
        ];
    }
}
