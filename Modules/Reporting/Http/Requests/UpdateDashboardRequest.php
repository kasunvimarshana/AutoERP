<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('dashboard'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'layout' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'is_shared' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
