<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

final readonly class DeleteNotificationTemplateCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
