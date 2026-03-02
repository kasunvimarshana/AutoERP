<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveGoodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'received_lines' => ['required', 'array', 'min:1'],
            'received_lines.*.line_id' => ['required', 'integer', 'min:1'],
            'received_lines.*.quantity_received' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
