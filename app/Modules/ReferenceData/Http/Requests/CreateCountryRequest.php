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

final class CreateCountryRequest extends ManageReferenceDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[A-Za-z]{2}$/'],
            'name' => ['required', 'string', 'max:150'],
            'phone_code' => ['nullable', 'string', 'regex:/^\+[0-9]{1,7}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
