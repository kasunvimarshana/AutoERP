<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class CreateTimezoneRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
