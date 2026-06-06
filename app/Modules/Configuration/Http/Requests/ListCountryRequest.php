<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Configuration\Constants\ConfigurationDefaults;

final class ListCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ConfigurationDefaults::MAX_PER_PAGE],
        ];
    }
}
