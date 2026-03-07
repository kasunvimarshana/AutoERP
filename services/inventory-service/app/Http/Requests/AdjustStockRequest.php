<?php

namespace App\Http\Requests;

use App\DTOs\StockAdjustmentDTO;
use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'           => ['required', 'string', 'in:add,subtract,set'],
            'quantity'       => ['required', 'integer', 'min:0'],
            'reason'         => ['required', 'string', 'max:500'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id'   => ['nullable', 'string', 'max:100'],
            'metadata'       => ['nullable', 'array'],
        ];
    }

    public function toDTO(?int $performedBy = null): StockAdjustmentDTO
    {
        return StockAdjustmentDTO::fromArray(array_merge(
            $this->validated(),
            ['performed_by' => $performedBy]
        ));
    }
}
