<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Notification\Application\Commands\UpdateNotificationTemplateCommand;
use Modules\Notification\Domain\Contracts\NotificationTemplateRepositoryInterface;
use Modules\Notification\Domain\Entities\NotificationTemplate;
use Modules\Notification\Domain\Enums\NotificationChannel;

class UpdateNotificationTemplateHandler extends BaseHandler
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateNotificationTemplateCommand $command): NotificationTemplate
    {
        return $this->transaction(function () use ($command): NotificationTemplate {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateNotificationTemplateCommand $cmd): NotificationTemplate {
                    $existing = $this->repository->findById($cmd->id, $cmd->tenantId);

                    if ($existing === null) {
                        throw new \DomainException("Notification template with ID '{$cmd->id}' not found.");
                    }

                    return $this->repository->save(new NotificationTemplate(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        channel: NotificationChannel::from($cmd->channel),
                        eventType: $cmd->eventType,
                        name: $cmd->name,
                        subject: $cmd->subject,
                        body: $cmd->body,
                        isActive: $cmd->isActive,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
