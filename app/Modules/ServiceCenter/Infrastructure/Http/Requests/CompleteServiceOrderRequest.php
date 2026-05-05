<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'parts_used' => ['sometimes', 'array'],
            'parts_used.*.part_name' => ['required_with:parts_used', 'string', 'max:255'],
            'parts_used.*.part_number' => ['required_with:parts_used', 'string', 'max:100'],
            'parts_used.*.quantity' => ['required_with:parts_used', 'integer', 'min:1'],
            'parts_used.*.unit_cost' => ['required_with:parts_used', 'numeric', 'min:0'],
            'parts_used.*.inventory_item_id' => ['nullable', 'uuid'],
        ];
    }
}
