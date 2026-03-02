<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;

class SendNotificationHandler extends BaseHandler
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(SendNotificationCommand $command): Notification
    {
        return $this->transaction(function () use ($command): Notification {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (SendNotificationCommand $cmd): Notification {
                    return $this->repository->save(new Notification(
                        id: null,
                        tenantId: $cmd->tenantId,
                        userId: $cmd->userId,
                        channel: NotificationChannel::from($cmd->channel),
                        eventType: $cmd->eventType,
                        templateId: $cmd->templateId,
                        subject: $cmd->subject,
                        body: $cmd->body,
                        status: NotificationStatus::Sent,
                        sentAt: Carbon::now()->toIso8601String(),
                        readAt: null,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
