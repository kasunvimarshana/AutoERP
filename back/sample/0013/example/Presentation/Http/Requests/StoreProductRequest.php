<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreProductRequest — Validates incoming product creation data.
 *
 * Sits at the boundary between HTTP and the Application layer.
 * Once validated, data is mapped to a CreateProductCommand in the controller.
 */
final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Implement authorization logic (e.g. Gate::allows('create-product'))
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'price_amount'   => ['required', 'integer', 'min:1'],
            'price_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_amount.min'        => 'Price must be at least 1 minor unit (e.g. 1 cent).',
            'price_currency.regex'    => 'Currency must be a valid 3-letter ISO 4217 code (e.g. USD).',
        ];
    }

    /**
     * Return price_amount as integer regardless of how it arrives in the request.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('price_currency')) {
            $this->merge(['price_currency' => strtoupper($this->price_currency)]);
        }
    }
}
