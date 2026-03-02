<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
