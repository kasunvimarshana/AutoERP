<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class CreateCurrencyRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'name' => ['required', 'string', 'max:150'],
            'symbol' => ['nullable', 'string', 'max:16'],
            'decimal_places' => ['sometimes', 'integer', 'min:0', 'max:8'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
