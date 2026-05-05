<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssetOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'        => ['required', 'integer', 'min:1'],
            'party_id'         => ['required', 'string', 'uuid'],
            'asset_id'         => ['required', 'string', 'uuid'],
            'ownership_type'   => ['required', 'string', 'in:owner,lessee,guarantor'],
            'acquisition_date' => ['required', 'date'],
            'disposal_date'    => ['nullable', 'date', 'after:acquisition_date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
