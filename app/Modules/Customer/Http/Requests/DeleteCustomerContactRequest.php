<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

class DeleteCustomerContactRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['row_version' => ['required', 'integer', 'min:1']];
    }
}
