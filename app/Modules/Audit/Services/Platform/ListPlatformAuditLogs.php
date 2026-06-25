<?php

declare(strict_types=1);

namespace Modules\Audit\Services\Platform;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Audit\Data\AuditCursorPage;
use Modules\Audit\Data\AuditQueryData;
use Modules\Audit\Data\AuditReadScope;
use Modules\Audit\Repositories\AuditLogReaderInterface;

final class ListPlatformAuditLogs
{
    public function __construct(
        private readonly PlatformAuditAuthorizationService $authorization,
        private readonly AuditLogReaderInterface $reader,
    ) {}

    public function execute(AuditQueryData $query, ?int $tenantId): AuditCursorPage
    {
        if (! $this->authorization->canView()) {
            throw new AuthorizationException('Viewing platform audit logs is not authorized.');
        }

        return $this->reader->cursorPage(AuditReadScope::forPlatform($tenantId), $query);
    }
}
