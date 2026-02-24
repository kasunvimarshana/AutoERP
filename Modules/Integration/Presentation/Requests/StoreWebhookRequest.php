<?php

namespace Modules\Integration\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'url'            => ['required', 'url', 'max:2048'],
            'events'         => ['nullable', 'array'],
            'events.*'       => ['string'],
            'signing_secret' => ['nullable', 'string', 'max:255'],
        ];
    }
}
