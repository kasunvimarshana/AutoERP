<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Crm\Application\Commands\DeleteLeadCommand;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;

class DeleteLeadHandler extends BaseHandler
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
    ) {}

    public function handle(DeleteLeadCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $lead = $this->leadRepository->findById($command->id, $command->tenantId);

            if ($lead === null) {
                throw new \DomainException("Lead with ID {$command->id} not found.");
            }

            $this->leadRepository->delete($command->id, $command->tenantId);
        });
    }
}
