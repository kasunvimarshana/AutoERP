<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertOrganizationUnitDocumentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        return [
            'expected_version' => $creating ? ['prohibited'] : ['required', 'integer', 'min:1'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'document_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'file' => [$creating ? 'required' : 'sometimes', 'file', 'max:'.max((int) config('organization-unit.storage.documents.max_size_kb', 10240), 1)],
            'file_path' => ['prohibited'],
            'object_key' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'size_bytes' => ['prohibited'],
            'checksum_sha256' => ['prohibited'],
        ];
    }
}
