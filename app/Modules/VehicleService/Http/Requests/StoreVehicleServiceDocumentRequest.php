<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceDocumentRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        $allowedTypes = config('vehicle-service.documents.allowed_types', []);
        $allowedMimeTypes = config('vehicle-service.documents.allowed_mime_types', []);
        $mimeTypeRule = 'mimetypes:'.implode(',', is_array($allowedMimeTypes) ? $allowedMimeTypes : []);
        $maxSizeKb = max((int) config('vehicle-service.documents.max_size_kb', 10240), 1);

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'document_type' => ['required', Rule::in(is_array($allowedTypes) ? $allowedTypes : [])],
            'file' => ['required', 'file', $mimeTypeRule, 'max:'.$maxSizeKb],
            'description' => ['nullable', 'string'],
        ];
    }
}
