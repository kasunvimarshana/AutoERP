<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Customization\Application\Commands\UpdateCustomFieldCommand;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Entities\CustomField;

class UpdateCustomFieldHandler extends BaseHandler
{
    public function __construct(
        private readonly CustomFieldRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateCustomFieldCommand $command): CustomField
    {
        return $this->transaction(function () use ($command): CustomField {
            $existing = $this->repository->findById($command->id, $command->tenantId);
            if ($existing === null) {
                throw new \DomainException('Custom field not found.');
            }

            return $this->pipeline
                ->send($command)
                ->through([ValidateCommandPipe::class, AuditLogPipe::class])
                ->then(function (UpdateCustomFieldCommand $cmd) use ($existing): CustomField {
                    $updated = new CustomField(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        entityType: $existing->entityType,
                        fieldKey: $existing->fieldKey,
                        fieldLabel: $cmd->fieldLabel,
                        fieldType: $existing->fieldType,
                        isRequired: $cmd->isRequired,
                        defaultValue: $cmd->defaultValue,
                        sortOrder: $cmd->sortOrder,
                        options: $cmd->options,
                        validationRules: $cmd->validationRules,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    );

                    return $this->repository->save($updated);
                });
        });
    }
}
