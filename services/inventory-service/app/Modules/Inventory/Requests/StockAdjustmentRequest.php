<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'type' => 'required|string|in:add,subtract',
            'reason' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
        ];
    }
}
