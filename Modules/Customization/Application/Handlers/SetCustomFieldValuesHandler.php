<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Customization\Application\Commands\SetCustomFieldValuesCommand;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Contracts\CustomFieldValueRepositoryInterface;

class SetCustomFieldValuesHandler extends BaseHandler
{
    public function __construct(
        private readonly CustomFieldRepositoryInterface $fieldRepository,
        private readonly CustomFieldValueRepositoryInterface $valueRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(SetCustomFieldValuesCommand $command): array
    {
        return $this->transaction(function () use ($command): array {
            return $this->pipeline
                ->send($command)
                ->through([ValidateCommandPipe::class, AuditLogPipe::class])
                ->then(function (SetCustomFieldValuesCommand $cmd): array {
                    $fields = $this->fieldRepository->findByEntityType($cmd->tenantId, $cmd->entityType);
                    $fieldMap = [];
                    foreach ($fields as $field) {
                        $fieldMap[$field->id] = $field;
                    }

                    foreach ($cmd->values as $valueData) {
                        $fieldId = (int) ($valueData['field_id'] ?? 0);
                        if (!isset($fieldMap[$fieldId])) {
                            throw new \DomainException(
                                "Custom field ID {$fieldId} does not exist for entity type '{$cmd->entityType}'."
                            );
                        }
                    }

                    return $this->valueRepository->replaceForEntity(
                        $cmd->tenantId,
                        $cmd->entityType,
                        $cmd->entityId,
                        $cmd->values
                    );
                });
        });
    }
}
