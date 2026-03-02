<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Notification\Application\Commands\CreateNotificationTemplateCommand;
use Modules\Notification\Domain\Contracts\NotificationTemplateRepositoryInterface;
use Modules\Notification\Domain\Entities\NotificationTemplate;
use Modules\Notification\Domain\Enums\NotificationChannel;

class CreateNotificationTemplateHandler extends BaseHandler
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateNotificationTemplateCommand $command): NotificationTemplate
    {
        return $this->transaction(function () use ($command): NotificationTemplate {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateNotificationTemplateCommand $cmd): NotificationTemplate {
                    $existing = $this->repository->findByEventType($cmd->tenantId, $cmd->eventType);
                    if ($existing !== null && $existing->channel->value === $cmd->channel) {
                        throw new \DomainException(
                            "A notification template for channel '{$cmd->channel}' and event '{$cmd->eventType}' already exists."
                        );
                    }

                    return $this->repository->save(new NotificationTemplate(
                        id: null,
                        tenantId: $cmd->tenantId,
                        channel: NotificationChannel::from($cmd->channel),
                        eventType: $cmd->eventType,
                        name: $cmd->name,
                        subject: $cmd->subject,
                        body: $cmd->body,
                        isActive: $cmd->isActive,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
