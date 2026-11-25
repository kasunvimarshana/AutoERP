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

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Services\UserAuthorizationService;

abstract class AuthorizedUserRequest extends FormRequest
{
    abstract protected function requiredPermission(): ?string;

    /** @return list<string> */
    protected function requiredPermissions(): array
    {
        $permission = $this->requiredPermission();

        return $permission === null ? [] : [$permission];
    }

    public function authorize(): bool
    {
        $permissions = $this->requiredPermissions();
        if ($permissions === []) {
            return $this->user() !== null;
        }

        $authorization = app(UserAuthorizationService::class);
        foreach ($permissions as $permission) {
            if ($authorization->canCurrent($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['email', 'username', 'status', 'platform', 'document_type'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([$field => mb_strtolower(trim((string) $this->input($field)))]);
            }
        }
    }
}
