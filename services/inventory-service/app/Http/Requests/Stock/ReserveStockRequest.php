<?php

declare(strict_types=1);

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reserve Stock Request - used for reserve, release, deduct, restore operations.
 */
class ReserveStockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid'],
            'warehouse_id' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'saga_id' => ['required', 'uuid'],
        ];
    }
}
