<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyChallengeRequest extends FormRequest
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
            'challenge_key' => ['required', 'string', 'max:120'],
            'challenge_secret' => ['required', 'string', 'min:1'],
        ];
    }
}
