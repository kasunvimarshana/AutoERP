<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Organisation\Application\Commands\CreateOrganisationCommand;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryInterface;
use Modules\Organisation\Domain\Entities\Organisation;
use Modules\Organisation\Domain\Enums\OrganisationStatus;

class CreateOrganisationHandler extends BaseHandler
{
    public function __construct(
        private readonly OrganisationRepositoryInterface $organisationRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateOrganisationCommand $command): Organisation
    {
        return $this->transaction(function () use ($command): Organisation {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateOrganisationCommand $cmd): Organisation {
                    $existing = $this->organisationRepository->findByCode(
                        strtoupper($cmd->code),
                        $cmd->tenantId
                    );

                    if ($existing !== null) {
                        throw new \DomainException(
                            "An organisation node with code '{$cmd->code}' already exists in this tenant."
                        );
                    }

                    if ($cmd->parentId !== null) {
                        $parent = $this->organisationRepository->findById(
                            $cmd->parentId,
                            $cmd->tenantId
                        );

                        if ($parent === null) {
                            throw new \DomainException(
                                "Parent organisation node with ID '{$cmd->parentId}' not found."
                            );
                        }
                    }

                    $organisation = new Organisation(
                        id: null,
                        tenantId: $cmd->tenantId,
                        parentId: $cmd->parentId,
                        type: $cmd->type,
                        name: $cmd->name,
                        code: strtoupper($cmd->code),
                        description: $cmd->description,
                        status: OrganisationStatus::Active->value,
                        meta: $cmd->meta,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->organisationRepository->save($organisation);
                });
        });
    }
}
