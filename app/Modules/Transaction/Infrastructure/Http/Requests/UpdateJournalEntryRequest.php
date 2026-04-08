<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_code'  => 'sometimes|required|string|max:20',
            'account_name'  => 'sometimes|required|string|max:255',
            'debit_amount'  => 'nullable|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'description'   => 'nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
