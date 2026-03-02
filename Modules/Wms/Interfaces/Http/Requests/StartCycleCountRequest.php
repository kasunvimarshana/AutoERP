<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartCycleCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
