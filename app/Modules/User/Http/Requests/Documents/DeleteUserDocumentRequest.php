<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Http\Requests\Documents;

use Modules\User\Http\Requests\AuthorizedUserRequest;

final class DeleteUserDocumentRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string
    {
        return null;
    }

    public function rules(): array
    {
        return ['expected_version' => ['required', 'integer', 'min:1']];
    }
}
