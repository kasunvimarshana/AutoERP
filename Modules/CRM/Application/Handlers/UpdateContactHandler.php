<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Crm\Application\Commands\UpdateContactCommand;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Entities\Contact;
use Modules\Crm\Domain\Enums\ContactStatus;

class UpdateContactHandler extends BaseHandler
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateContactCommand $command): Contact
    {
        return $this->transaction(function () use ($command): Contact {
            $existing = $this->contactRepository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Contact with ID {$command->id} not found.");
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateContactCommand $cmd) use ($existing): Contact {
                    $updated = new Contact(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        firstName: $cmd->firstName ?? $existing->firstName,
                        lastName: $cmd->lastName ?? $existing->lastName,
                        email: $cmd->email ?? $existing->email,
                        phone: $cmd->phone ?? $existing->phone,
                        company: $cmd->company ?? $existing->company,
                        jobTitle: $cmd->jobTitle ?? $existing->jobTitle,
                        status: $cmd->status !== null ? ContactStatus::from($cmd->status) : $existing->status,
                        notes: $cmd->notes ?? $existing->notes,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    );

                    return $this->contactRepository->save($updated);
                });
        });
    }
}
