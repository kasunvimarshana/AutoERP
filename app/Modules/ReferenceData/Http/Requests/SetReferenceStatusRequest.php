<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

final class SetReferenceStatusRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
