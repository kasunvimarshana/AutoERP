<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class StoreVehicleServiceDocumentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'document_type' => ['required', Rule::in(['image', 'inspection_report', 'warranty', 'invoice_copy', 'other'])],
            'file' => ['nullable', 'file', 'max:10240'],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
        ];
    }
}
