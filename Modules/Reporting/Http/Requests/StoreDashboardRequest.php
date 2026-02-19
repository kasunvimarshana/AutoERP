<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Reporting\Models\Dashboard::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'layout' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'is_shared' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
