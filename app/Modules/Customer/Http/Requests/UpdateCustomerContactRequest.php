<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

final class UpdateCustomerContactRequest extends CustomerContactRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'row_version' => ['required', 'integer', 'min:1']];
    }
}
