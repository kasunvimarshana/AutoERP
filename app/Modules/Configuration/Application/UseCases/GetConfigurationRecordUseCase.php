<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Domain\Contracts\ConfigurationReadRepositoryContract;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;

class GetConfigurationRecordUseCase
{
    public function __construct(
        private readonly ConfigurationReadRepositoryContract $readRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(ConfigurationRecordType $type, int $id): array
    {
        $record = $this->readRepository->find($type, $id);
        if ($record === null) {
            throw ConfigurationRecordNotFoundException::forId($type, $id);
        }

        return $record;
    }
}
