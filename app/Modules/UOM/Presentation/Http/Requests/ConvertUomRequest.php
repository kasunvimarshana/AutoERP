<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_uom_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'to_uom_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'quantity' => ['required', 'numeric'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
        ];
    }
}
