<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Organisation\Application\Commands\UpdateOrganisationCommand;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryInterface;
use Modules\Organisation\Domain\Entities\Organisation;
use Modules\Organisation\Domain\Enums\OrganisationStatus;

class UpdateOrganisationHandler extends BaseHandler
{
    public function __construct(
        private readonly OrganisationRepositoryInterface $organisationRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateOrganisationCommand $command): Organisation
    {
        return $this->transaction(function () use ($command): Organisation {
            $existing = $this->organisationRepository->findById(
                $command->id,
                $command->tenantId
            );

            if ($existing === null) {
                throw new \DomainException(
                    "Organisation node with ID '{$command->id}' not found."
                );
            }

            if ($command->parentId !== null && $command->parentId !== $existing->parentId) {
                $parent = $this->organisationRepository->findById(
                    $command->parentId,
                    $command->tenantId
                );

                if ($parent === null) {
                    throw new \DomainException(
                        "Parent organisation node with ID '{$command->parentId}' not found."
                    );
                }

                if ($parent->id === $existing->id) {
                    throw new \DomainException('An organisation node cannot be its own parent.');
                }
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateOrganisationCommand $cmd) use ($existing): Organisation {
                    $updated = new Organisation(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        parentId: $cmd->parentId ?? $existing->parentId,
                        type: $existing->type,
                        name: $cmd->name,
                        code: $existing->code,
                        description: $cmd->description ?? $existing->description,
                        status: $cmd->status !== null
                            ? OrganisationStatus::from($cmd->status)->value
                            : $existing->status,
                        meta: $cmd->meta ?? $existing->meta,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    );

                    return $this->organisationRepository->save($updated);
                });
        });
    }
}
