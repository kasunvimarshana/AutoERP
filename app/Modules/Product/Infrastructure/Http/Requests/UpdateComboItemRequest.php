<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComboItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity'       => 'required|numeric|min:0.0001',
            'price_override' => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|size:3',
            'sort_order'     => 'nullable|integer|min:0',
            'metadata'       => 'nullable|array',
        ];
    }
}
