<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseRentalDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'released_amount' => ['sometimes', 'numeric', 'min:0'],
            'released_at' => ['sometimes', 'date'],
            'payment_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'journal_entry_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
