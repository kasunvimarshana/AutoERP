<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class UpdateTimezoneRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'name' => ['prohibited'],
            'display_name' => ['sometimes', 'required', 'string', 'max:150'],
            'is_active' => ['prohibited'],
        ];
    }
}
