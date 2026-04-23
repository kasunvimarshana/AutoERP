<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSerialRequest extends FormRequest
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
            'batch_id' => 'nullable|integer|exists:batches,id',
            'serial_number' => 'required|string|max:100',
            'status' => 'nullable|string|max:50',
            'sold_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
