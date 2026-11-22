<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Http\Requests;

final class UpdateUserQuickAccessRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string
    {
        return null;
    }

    public function rules(): array
    {
        return [
            'items' => ['present', 'array', 'max:50'],
            'items.*.id' => ['required', 'string', 'max:100'],
            'items.*.label' => ['required', 'string', 'max:100'],
            'items.*.to' => ['required', 'string', 'max:255', 'regex:/^\/(?!\/|\\\\)/'],
            'items.*.group' => ['required', 'string', 'max:100'],
            'items.*.icon' => ['required', 'string', 'max:50'],
            'items.*.enabled' => ['required', 'boolean'],
            'items.*.order' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }
}
