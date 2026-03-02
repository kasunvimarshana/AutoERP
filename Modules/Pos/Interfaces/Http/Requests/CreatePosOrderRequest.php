<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'pos_session_id' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'min:1'],
            'lines.*.product_name' => ['required', 'string', 'max:255'],
            'lines.*.sku' => ['required', 'string', 'max:100'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
