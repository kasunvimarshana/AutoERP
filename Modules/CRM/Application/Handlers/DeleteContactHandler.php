<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Crm\Application\Commands\DeleteContactCommand;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;

class DeleteContactHandler extends BaseHandler
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
    ) {}

    public function handle(DeleteContactCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $contact = $this->contactRepository->findById($command->id, $command->tenantId);

            if ($contact === null) {
                throw new \DomainException("Contact with ID {$command->id} not found.");
            }

            $this->contactRepository->delete($command->id, $command->tenantId);
        });
    }
}
