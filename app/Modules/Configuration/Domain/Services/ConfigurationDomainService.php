<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Services;

use Modules\Configuration\Domain\Aggregates\ConfigurationRecordAggregate;
use Modules\Configuration\Domain\Contracts\ConfigurationReadRepositoryContract;
use Modules\Configuration\Domain\Contracts\ConfigurationWriteRepositoryContract;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;
use Modules\Configuration\Domain\Exceptions\ConfigurationConflictException;
use Modules\Configuration\Domain\Exceptions\ConfigurationDeletionBlockedException;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;

class ConfigurationDomainService
{
    public function __construct(
        private readonly ConfigurationReadRepositoryContract $readRepository,
        private readonly ConfigurationWriteRepositoryContract $writeRepository,
    ) {
    }

    public function assertRecordCanBeSaved(ConfigurationRecordAggregate $aggregate): void
    {
        $payload = $aggregate->payload();
        $uniqueField = $aggregate->type()->uniqueField();
        $uniqueValue = (string) ($payload[$uniqueField] ?? '');

        if ($uniqueValue === '') {
            throw new ConfigurationConflictException(sprintf('%s is required.', $uniqueField));
        }

        $exists = $this->readRepository->existsByUniqueField(
            $aggregate->type(),
            $uniqueValue,
            $aggregate->id(),
        );

        if ($exists) {
            throw new ConfigurationConflictException(sprintf(
                'Duplicate %s for %s: %s',
                $uniqueField,
                $aggregate->type()->value,
                $uniqueValue,
            ));
        }
    }

    public function assertRecordCanBeDeleted(ConfigurationRecordType $type, int $id): void
    {
        $record = $this->readRepository->find($type, $id);
        if ($record === null) {
            throw ConfigurationRecordNotFoundException::forId($type, $id);
        }

        $dependencies = $this->writeRepository->dependencyCounts($type, $id);
        if ($dependencies !== []) {
            throw ConfigurationDeletionBlockedException::dueToDependencies($type, $id, $dependencies);
        }
    }
}
