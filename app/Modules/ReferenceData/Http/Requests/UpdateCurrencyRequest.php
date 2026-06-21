<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class UpdateCurrencyRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'code' => ['prohibited'],
            'decimal_places' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'symbol' => ['sometimes', 'nullable', 'string', 'max:16'],
            'is_active' => ['prohibited'],
        ];
    }
}
