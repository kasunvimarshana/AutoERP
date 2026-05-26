<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts\UseCases\AuditLogs;

use Modules\Audit\Application\DTOs\AuditLogActivityData;
use Modules\Core\Application\Results\Result;

interface LogActivityServiceInterface
{
    public function execute(AuditLogActivityData $data): Result;
}
