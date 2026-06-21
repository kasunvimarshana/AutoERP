<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Modules\Audit\Repositories\AuditLogReaderInterface;
use Modules\Core\DTOs\DataRecord;

final class GetAuditLog
{
    public function __construct(
        private readonly AuditAuthorizationService $authorization,
        private readonly AuditLogReaderInterface $reader,
    ) {}

    public function execute(int $id): ?DataRecord
    {
        return $this->reader->findVisibleById($this->authorization->resolveReadScope(), $id);
    }
}
