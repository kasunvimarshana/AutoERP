<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts\UseCases\AuditLogs;

use Modules\Core\Application\Results\Result;

interface UpdateAuditLogServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}