<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use Modules\Audit\Data\AuditCursorPage;
use Modules\Audit\Data\AuditQueryData;
use Modules\Audit\Data\AuditReadScope;
use Modules\Core\DTOs\DataRecord;

interface AuditLogReaderInterface
{
    public function findVisibleById(AuditReadScope $scope, int $id): ?DataRecord;

    public function cursorPage(AuditReadScope $scope, AuditQueryData $query): AuditCursorPage;
}
