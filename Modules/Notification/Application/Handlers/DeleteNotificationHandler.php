<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Notification\Application\Commands\DeleteNotificationCommand;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;

class DeleteNotificationHandler extends BaseHandler
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {}

    public function handle(DeleteNotificationCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Notification with ID '{$command->id}' not found.");
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
