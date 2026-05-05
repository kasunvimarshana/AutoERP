<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldRentalDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency_id' => ['required', 'integer', 'min:1'],
            'held_amount' => ['required', 'numeric', 'min:0'],
            'org_unit_id' => ['sometimes', 'integer', 'min:1'],
            'held_at' => ['sometimes', 'date'],
            'payment_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
