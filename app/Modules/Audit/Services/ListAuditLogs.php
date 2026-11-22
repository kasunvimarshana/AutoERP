<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Audit\Services;

use Modules\Audit\Data\AuditCursorPage;
use Modules\Audit\Data\AuditQueryData;
use Modules\Audit\Repositories\AuditLogReaderInterface;

final class ListAuditLogs
{
    public function __construct(
        private readonly AuditAuthorizationService $authorization,
        private readonly AuditLogReaderInterface $reader,
    ) {}

    public function execute(AuditQueryData $query): AuditCursorPage
    {
        return $this->reader->cursorPage($this->authorization->resolveReadScope(), $query);
    }
}
