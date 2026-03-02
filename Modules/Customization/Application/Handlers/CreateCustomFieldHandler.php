<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Customization\Application\Commands\CreateCustomFieldCommand;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Entities\CustomField;

class CreateCustomFieldHandler extends BaseHandler
{
    public function __construct(
        private readonly CustomFieldRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateCustomFieldCommand $command): CustomField
    {
        return $this->transaction(function () use ($command): CustomField {
            return $this->pipeline
                ->send($command)
                ->through([ValidateCommandPipe::class, AuditLogPipe::class])
                ->then(function (CreateCustomFieldCommand $cmd): CustomField {
                    $existing = $this->repository->findByEntityType($cmd->tenantId, $cmd->entityType);
                    foreach ($existing as $field) {
                        if ($field->fieldKey === $cmd->fieldKey) {
                            throw new \DomainException(
                                "A custom field with key '{$cmd->fieldKey}' already exists for entity type '{$cmd->entityType}'."
                            );
                        }
                    }

                    $field = new CustomField(
                        id: null,
                        tenantId: $cmd->tenantId,
                        entityType: $cmd->entityType,
                        fieldKey: $cmd->fieldKey,
                        fieldLabel: $cmd->fieldLabel,
                        fieldType: $cmd->fieldType,
                        isRequired: $cmd->isRequired,
                        defaultValue: $cmd->defaultValue,
                        sortOrder: $cmd->sortOrder,
                        options: $cmd->options,
                        validationRules: $cmd->validationRules,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->repository->save($field);
                });
        });
    }
}
