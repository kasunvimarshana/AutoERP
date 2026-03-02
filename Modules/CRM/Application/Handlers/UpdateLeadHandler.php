<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Crm\Application\Commands\UpdateLeadCommand;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Domain\Entities\Lead;
use Modules\Crm\Domain\Enums\LeadStatus;

class UpdateLeadHandler extends BaseHandler
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateLeadCommand $command): Lead
    {
        return $this->transaction(function () use ($command): Lead {
            $existing = $this->leadRepository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Lead with ID {$command->id} not found.");
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateLeadCommand $cmd) use ($existing): Lead {
                    $status = $cmd->status !== null
                        ? LeadStatus::from($cmd->status)
                        : $existing->status;

                    $estimatedValue = $cmd->estimatedValue !== null
                        ? bcadd((string) $cmd->estimatedValue, '0', 4)
                        : $existing->estimatedValue;

                    $updated = new Lead(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        contactId: $cmd->contactId ?? $existing->contactId,
                        title: $cmd->title ?? $existing->title,
                        description: $cmd->description ?? $existing->description,
                        status: $status,
                        estimatedValue: $estimatedValue,
                        currency: $cmd->currency ?? $existing->currency,
                        expectedCloseDate: $cmd->expectedCloseDate ?? $existing->expectedCloseDate,
                        notes: $cmd->notes ?? $existing->notes,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    );

                    return $this->leadRepository->save($updated);
                });
        });
    }
}
