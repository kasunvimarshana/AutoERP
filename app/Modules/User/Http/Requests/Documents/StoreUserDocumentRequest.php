<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Documents;

use Illuminate\Validation\Rule;
use Modules\User\Constants\UserDocumentType;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class StoreUserDocumentRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return null; }
    public function rules(): array
    {
        $maxKilobytes = max(1, (int) config('user.storage.documents.max_size_kb', 10240));
        return [
            'name' => ['required', 'string', 'max:160'],
            'document_type' => ['required', Rule::in(UserDocumentType::values())],
            'file' => ['required', 'file', 'max:'.$maxKilobytes],
            'file_path' => ['prohibited'],
            'object_key' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'size_bytes' => ['prohibited'],
            'checksum_sha256' => ['prohibited'],
            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }
}
