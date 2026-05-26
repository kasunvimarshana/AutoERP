<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts\UseCases\AuditLogs;

use Modules\Audit\Application\DTOs\AuditLogEntityChangeData;
use Modules\Core\Application\Results\Result;

interface LogEntityChangeServiceInterface
{
    public function execute(AuditLogEntityChangeData $data): Result;
}
