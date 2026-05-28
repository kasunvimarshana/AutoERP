<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConvertUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id'   => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'quantity'    => ['required', 'numeric'],
            'from_uom_id' => ['required', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'to_uom_id'   => ['required', 'integer', 'min:1', 'exists:unit_of_measures,id', 'different:from_uom_id'],
            'item_id'     => ['nullable', 'integer', 'min:1', 'exists:items,id'],
        ];
    }
}
