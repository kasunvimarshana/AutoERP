<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts\UseCases\AuditLogs;

use Modules\Audit\Application\DTOs\AuditLogQueryData;
use Modules\Core\Application\Results\Result;

interface ListAuditLogsServiceInterface
{
    public function execute(AuditLogQueryData $query): Result;
}