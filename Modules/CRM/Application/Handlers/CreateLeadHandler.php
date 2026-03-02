<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Crm\Application\Commands\CreateLeadCommand;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Domain\Entities\Lead;
use Modules\Crm\Domain\Enums\LeadStatus;

class CreateLeadHandler extends BaseHandler
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateLeadCommand $command): Lead
    {
        return $this->transaction(function () use ($command): Lead {
            if ($command->contactId !== null) {
                $contact = $this->contactRepository->findById($command->contactId, $command->tenantId);
                if ($contact === null) {
                    throw new \DomainException("Contact with ID {$command->contactId} not found.");
                }
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateLeadCommand $cmd): Lead {
                    $status = LeadStatus::from($cmd->status ?? LeadStatus::New->value);
                    $estimatedValue = bcadd((string) ($cmd->estimatedValue ?? '0'), '0', 4);
                    $currency = $cmd->currency ?? config('currency.default', 'LKR');

                    $lead = new Lead(
                        id: null,
                        tenantId: $cmd->tenantId,
                        contactId: $cmd->contactId,
                        title: $cmd->title,
                        description: $cmd->description,
                        status: $status,
                        estimatedValue: $estimatedValue,
                        currency: $currency,
                        expectedCloseDate: $cmd->expectedCloseDate,
                        notes: $cmd->notes,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->leadRepository->save($lead);
                });
        });
    }
}
