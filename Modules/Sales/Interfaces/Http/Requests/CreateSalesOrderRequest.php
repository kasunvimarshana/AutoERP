<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'order_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'currency' => ['nullable', 'string', 'size:3', 'in:'.implode(',', config('currency.supported', ['LKR']))],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
