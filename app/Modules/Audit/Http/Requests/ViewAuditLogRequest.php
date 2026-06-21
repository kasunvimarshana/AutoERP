<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Requests;

use Modules\Audit\Services\AuditAuthorizationService;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class ViewAuditLogRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(AuditAuthorizationService::class)->canViewCurrent();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
