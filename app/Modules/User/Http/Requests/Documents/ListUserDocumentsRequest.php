<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Documents;

use Illuminate\Validation\Rule;
use Modules\User\Constants\UserDocumentType;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class ListUserDocumentsRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return null; }
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'document_type' => ['nullable', Rule::in(UserDocumentType::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
