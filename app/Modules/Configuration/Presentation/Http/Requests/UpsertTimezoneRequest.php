<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTimezoneRequest extends FormRequest
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
            'name' => array_merge($required, ['string', 'max:255']),
            'offset' => array_merge($required, ['string', 'max:255']),
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
