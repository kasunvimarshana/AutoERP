<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Crm\Application\Commands\DeleteActivityCommand;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;

class DeleteActivityHandler extends BaseHandler
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(DeleteActivityCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $activity = $this->activityRepository->findById($command->id, $command->tenantId);

            if ($activity === null) {
                throw new \DomainException("Activity with ID {$command->id} not found.");
            }

            $this->activityRepository->delete($command->id, $command->tenantId);
        });
    }
}
