<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class UpdateCountryRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'code' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone_code' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^\+[0-9]{1,7}$/',
            ],
            'is_active' => ['prohibited'],
        ];
    }
}
