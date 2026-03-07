<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReserveStockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'order_id'   => ['required', 'uuid'],
        ];
    }
}
