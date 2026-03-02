<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Notification\Application\Commands\MarkNotificationReadCommand;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Domain\Entities\Notification;

class MarkNotificationReadHandler extends BaseHandler
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(MarkNotificationReadCommand $command): ?Notification
    {
        return $this->transaction(function () use ($command): ?Notification {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (MarkNotificationReadCommand $cmd): ?Notification {
                    $existing = $this->repository->findById($cmd->id, $cmd->tenantId);

                    if ($existing === null) {
                        throw new \DomainException("Notification with ID '{$cmd->id}' not found.");
                    }

                    return $this->repository->markRead($cmd->id, $cmd->tenantId);
                });
        });
    }
}
