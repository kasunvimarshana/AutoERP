<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordCycleCountLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'product_id' => ['required', 'integer', 'min:1'],
            'bin_id' => ['nullable', 'integer', 'min:1'],
            'system_qty' => ['required', 'numeric', 'min:0'],
            'counted_qty' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
