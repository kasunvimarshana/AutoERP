<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGs1IdentifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier_type'  => 'sometimes|required|string|in:gtin,gln,sscc,grai,giai,gcp',
            'identifier_value' => 'sometimes|required|string|max:255',
            'entity_type'      => 'nullable|string|max:100',
            'entity_id'        => 'nullable|integer',
            'is_active'        => 'sometimes|required|boolean',
            'metadata'         => 'nullable|array',
        ];
    }
}
