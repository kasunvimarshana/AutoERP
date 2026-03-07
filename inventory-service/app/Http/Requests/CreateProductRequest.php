<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku'         => ['required', 'string', 'max:100', 'unique:products,sku'],
            'price'       => ['required', 'numeric', 'min:0'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'status'      => ['sometimes', 'string', 'in:active,inactive,discontinued'],
            'metadata'    => ['sometimes', 'array'],
        ];
    }
}
