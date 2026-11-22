<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
