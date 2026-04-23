<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'batch_number' => 'required|string|max:100',
            'lot_number' => 'nullable|string|max:100',
            'manufactured_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'quantity' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
