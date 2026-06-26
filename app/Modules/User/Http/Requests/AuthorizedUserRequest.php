<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Services\UserAuthorizationService;

abstract class AuthorizedUserRequest extends FormRequest
{
    abstract protected function requiredPermission(): ?string;

    public function authorize(): bool
    {
        $permission = $this->requiredPermission();

        return $permission === null
            ? $this->user() !== null
            : app(UserAuthorizationService::class)->canCurrent($permission);
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
