<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Crm\Application\Commands\CreateContactCommand;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Entities\Contact;
use Modules\Crm\Domain\Enums\ContactStatus;

class CreateContactHandler extends BaseHandler
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateContactCommand $command): Contact
    {
        return $this->transaction(function () use ($command): Contact {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateContactCommand $cmd): Contact {
                    $contact = new Contact(
                        id: null,
                        tenantId: $cmd->tenantId,
                        firstName: $cmd->firstName,
                        lastName: $cmd->lastName,
                        email: $cmd->email,
                        phone: $cmd->phone,
                        company: $cmd->company,
                        jobTitle: $cmd->jobTitle,
                        status: ContactStatus::Active,
                        notes: $cmd->notes,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->contactRepository->save($contact);
                });
        });
    }
}
