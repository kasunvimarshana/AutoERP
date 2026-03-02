<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Crm\Application\Commands\LogActivityCommand;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Domain\Entities\Activity;
use Modules\Crm\Domain\Enums\ActivityType;

class LogActivityHandler extends BaseHandler
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(LogActivityCommand $command): Activity
    {
        return $this->transaction(function () use ($command): Activity {
            if ($command->contactId !== null) {
                $contact = $this->contactRepository->findById($command->contactId, $command->tenantId);
                if ($contact === null) {
                    throw new \DomainException("Contact with ID {$command->contactId} not found.");
                }
            }

            if ($command->leadId !== null) {
                $lead = $this->leadRepository->findById($command->leadId, $command->tenantId);
                if ($lead === null) {
                    throw new \DomainException("Lead with ID {$command->leadId} not found.");
                }
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (LogActivityCommand $cmd): Activity {
                    $activity = new Activity(
                        id: null,
                        tenantId: $cmd->tenantId,
                        contactId: $cmd->contactId,
                        leadId: $cmd->leadId,
                        type: ActivityType::from($cmd->type),
                        subject: $cmd->subject,
                        description: $cmd->description,
                        scheduledAt: $cmd->scheduledAt,
                        completedAt: $cmd->completedAt,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->activityRepository->save($activity);
                });
        });
    }
}
