<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListProductIdentifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'product_id' => 'nullable|integer|min:1',
            'variant_id' => 'nullable|integer|min:1',
            'technology' => 'nullable|string|in:barcode_1d,barcode_2d,qr_code,rfid_hf,rfid_uhf,nfc,gs1_epc,custom',
            'format' => 'nullable|string|in:ean13,ean8,upc_a,code128,code39,qr,datamatrix,gs1_128,epc_sgtin,other',
            'value' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ];
    }
}
