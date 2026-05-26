<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResolveUserByIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
