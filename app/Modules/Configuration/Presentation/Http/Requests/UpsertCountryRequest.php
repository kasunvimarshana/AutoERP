<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCountryRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'code' => array_merge($required, ['string', 'max:255']),
            'name' => array_merge($required, ['string', 'max:255']),
            'phone_code' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
