<?php

declare(strict_types=1);

namespace Modules\Audit\Services\Platform;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Audit\Data\AuditReadScope;
use Modules\Audit\Repositories\AuditLogReaderInterface;
use Modules\Core\DTOs\DataRecord;

final class GetPlatformAuditLog
{
    public function __construct(
        private readonly PlatformAuditAuthorizationService $authorization,
        private readonly AuditLogReaderInterface $reader,
    ) {}

    public function execute(int $id): ?DataRecord
    {
        if (! $this->authorization->canView()) {
            throw new AuthorizationException('Viewing platform audit logs is not authorized.');
        }

        return $this->reader->findVisibleById(AuditReadScope::forPlatform(), $id);
    }
}
