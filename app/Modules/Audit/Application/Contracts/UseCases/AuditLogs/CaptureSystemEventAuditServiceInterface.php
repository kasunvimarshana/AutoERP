<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts\UseCases\AuditLogs;

use Modules\Core\Application\Results\Result;

interface CaptureSystemEventAuditServiceInterface
{
    public function execute(string $eventName, mixed $eventPayload = null): Result;
}
