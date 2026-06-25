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
            'channel' => ['nullable', 'string', 'max:60'],
            'target' => ['required', 'string', 'max:320'],
            'challenge_type' => ['nullable', 'string', 'max:60'],
            'ttl_seconds' => ['nullable', 'integer', 'min:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
