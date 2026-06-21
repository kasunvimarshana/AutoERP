<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class CreateLanguageRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:15',
                'regex:/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/',
            ],
            'name' => ['required', 'string', 'max:150'],
            'native_name' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
