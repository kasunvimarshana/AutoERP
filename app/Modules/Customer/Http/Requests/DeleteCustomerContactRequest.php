<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

class DeleteCustomerContactRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['row_version' => ['required', 'integer', 'min:1']];
    }
}
