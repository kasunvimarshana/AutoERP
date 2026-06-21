<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class UpdateLanguageRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'code' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'native_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'is_active' => ['prohibited'],
        ];
    }
}
