<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Documents;

use Modules\User\Http\Requests\AuthorizedUserRequest;

final class DeleteUserDocumentRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return null; }
    public function rules(): array { return ['expected_version' => ['required', 'integer', 'min:1']]; }
}
