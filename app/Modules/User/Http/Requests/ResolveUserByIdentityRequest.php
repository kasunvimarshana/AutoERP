<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class ResolveUserByIdentityRequest extends FormRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return $this->canUse(UserPermission::USERS_VIEW);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_key' => ['required', 'string', 'max:120'],
            'provider_user_key' => ['required', 'string', 'max:255'],
        ];
    }
}
