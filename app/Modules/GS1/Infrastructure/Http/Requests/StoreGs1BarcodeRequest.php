<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGs1BarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'               => 'required|integer',
            'gs1_identifier_id'       => 'required|integer',
            'barcode_type'            => 'required|string|in:gs1_128,ean_13,ean_8,upc_a,datamatrix,qr_code',
            'barcode_data'            => 'required|string',
            'application_identifiers' => 'nullable|string|max:1000',
            'is_primary'              => 'boolean',
            'is_active'               => 'boolean',
            'metadata'                => 'nullable|array',
        ];
    }
}
