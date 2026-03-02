<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'aisle_id' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
