<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts\UseCases\AuditLogs;

use Modules\Core\Application\Results\Result;

interface GetAuditLogServiceInterface
{
    public function execute(int|string $id): Result;
}