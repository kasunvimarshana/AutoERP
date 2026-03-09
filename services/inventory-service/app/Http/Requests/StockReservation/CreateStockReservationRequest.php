<?php

namespace App\Http\Requests\StockReservation;

use Illuminate\Foundation\Http\FormRequest;

class CreateStockReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'     => ['required', 'uuid', 'exists:products,id'],
            'warehouse_id'   => ['required', 'uuid', 'exists:warehouses,id'],
            'quantity'       => ['required', 'integer', 'min:1'],
            'reference_id'   => ['required', 'string', 'max:255'],
            'reference_type' => ['required', 'string', 'max:100'],
            'expires_at'     => ['nullable', 'date', 'after:now'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'metadata'       => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.after' => 'Reservation expiry must be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('expires_at') || !$this->input('expires_at')) {
            $ttl = config('inventory.reservation_ttl', 3600);
            $this->merge([
                'expires_at' => now()->addSeconds($ttl)->toISOString(),
            ]);
        }
    }
}
