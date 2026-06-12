<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class StoreVehicleServiceDocumentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $mimeTypes = config('extension.attachments.allowed_mime_types', []);

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'document_type' => ['required', Rule::in(['image', 'inspection_report', 'warranty', 'invoice_copy', 'other'])],
            'file' => [
                'required',
                'file',
                'max:'.(int) config('extension.attachments.max_upload_kilobytes', 51200),
                'mimetypes:'.implode(',', is_array($mimeTypes) ? $mimeTypes : []),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
