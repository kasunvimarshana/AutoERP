<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGs1BarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode_type'            => 'sometimes|required|string|in:gs1_128,ean_13,ean_8,upc_a,datamatrix,qr_code',
            'barcode_data'            => 'sometimes|required|string',
            'application_identifiers' => 'nullable|string|max:1000',
            'is_primary'              => 'sometimes|required|boolean',
            'is_active'               => 'sometimes|required|boolean',
            'metadata'                => 'nullable|array',
        ];
    }
}
