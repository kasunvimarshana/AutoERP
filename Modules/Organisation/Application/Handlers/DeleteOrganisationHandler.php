<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Organisation\Application\Commands\DeleteOrganisationCommand;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryInterface;

class DeleteOrganisationHandler extends BaseHandler
{
    public function __construct(
        private readonly OrganisationRepositoryInterface $organisationRepository,
    ) {}

    public function handle(DeleteOrganisationCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $organisation = $this->organisationRepository->findById($command->id, $command->tenantId);

            if ($organisation === null) {
                throw new \DomainException('Organisation node not found.');
            }

            $this->organisationRepository->delete($command->id, $command->tenantId);
        });
    }
}
