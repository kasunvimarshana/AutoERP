<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Documents;

use Illuminate\Validation\Rule;
use Modules\User\Constants\UserDocumentType;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class UpdateUserDocumentRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return null; }
    public function rules(): array
    {
        $maxKilobytes = max(1, (int) config('user.storage.documents.max_size_kb', 10240));
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'document_type' => ['sometimes', Rule::in(UserDocumentType::values())],
            'file' => ['sometimes', 'file', 'max:'.$maxKilobytes],
            'file_path' => ['prohibited'],
            'object_key' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'size_bytes' => ['prohibited'],
            'checksum_sha256' => ['prohibited'],
        ];
    }
}
