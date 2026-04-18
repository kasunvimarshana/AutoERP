<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductIdentifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'serial_id' => 'nullable|integer|exists:serials,id',
            'technology' => 'required|string|in:barcode_1d,barcode_2d,qr_code,rfid_hf,rfid_uhf,nfc,gs1_epc,custom',
            'format' => 'nullable|string|in:ean13,ean8,upc_a,code128,code39,qr,datamatrix,gs1_128,epc_sgtin,other',
            'value' => 'required|string|max:255',
            'gs1_company_prefix' => 'nullable|string|max:255',
            'gs1_application_identifiers' => 'nullable|array',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'format_config' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
