<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RequestVerificationChallengeRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'provider_id' => ['nullable', 'integer', 'min:1'],
            'identity_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'channel' => ['nullable', 'string', 'max:60'],
            'target' => ['required', 'string', 'max:320'],
            'challenge_type' => ['nullable', 'string', 'max:60'],
            'ttl_seconds' => ['nullable', 'integer', 'min:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
