<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockReturnLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity_approved' => 'sometimes|nullable|numeric|min:0',
            'condition'         => 'sometimes|nullable|string|in:good,damaged,defective,expired',
            'disposition'       => 'sometimes|nullable|string|in:restock,scrap,vendor_return,quarantine',
            'notes'             => 'sometimes|nullable|string',
        ];
    }
}
