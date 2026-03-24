<?php

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language'     => 'nullable|string|in:en,es,fr,de',
            'timezone'     => 'nullable|string|timezone',
            'notifications'=> 'nullable|array',
        ];
    }
}
