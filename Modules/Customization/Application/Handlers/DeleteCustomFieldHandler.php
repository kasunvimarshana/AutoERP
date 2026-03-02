<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Customization\Application\Commands\DeleteCustomFieldCommand;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Contracts\CustomFieldValueRepositoryInterface;

class DeleteCustomFieldHandler extends BaseHandler
{
    public function __construct(
        private readonly CustomFieldRepositoryInterface $repository,
        private readonly CustomFieldValueRepositoryInterface $valueRepository,
    ) {}

    public function handle(DeleteCustomFieldCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);
            if ($existing === null) {
                throw new \DomainException('Custom field not found.');
            }

            $this->valueRepository->deleteByField($command->id, $command->tenantId);
            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
