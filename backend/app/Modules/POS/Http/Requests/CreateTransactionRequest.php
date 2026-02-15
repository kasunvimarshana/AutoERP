<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\POS\Models\POSTransaction::class);
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'cashier_id' => ['required', 'integer', 'exists:users,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.product_sku' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:none,flat,percentage'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string'],
            'items.*.serial_number' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('cashier_id')) {
            $this->merge(['cashier_id' => $this->user()->id]);
        }
    }
}
