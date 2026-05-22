<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Orchestrators;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\DTOs\DeleteConfigurationRecordDTO;
use Modules\Configuration\Application\DTOs\UpsertConfigurationRecordDTO;
use Modules\Configuration\Application\UseCases\DeleteConfigurationRecordUseCase;
use Modules\Configuration\Application\UseCases\GetConfigurationRecordUseCase;
use Modules\Configuration\Application\UseCases\ListConfigurationRecordsUseCase;
use Modules\Configuration\Application\UseCases\UpsertConfigurationRecordUseCase;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

class ConfigurationRecordOrchestrator
{
    public function __construct(
        private readonly ListConfigurationRecordsUseCase $listUseCase,
        private readonly GetConfigurationRecordUseCase $getUseCase,
        private readonly UpsertConfigurationRecordUseCase $upsertUseCase,
        private readonly DeleteConfigurationRecordUseCase $deleteUseCase,
    ) {
    }

    public function list(ConfigurationRecordType $type, int $perPage = 20): LengthAwarePaginator
    {
        return $this->listUseCase->execute($type, $perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function show(ConfigurationRecordType $type, int $id): array
    {
        return $this->getUseCase->execute($type, $id);
    }

    public function upsert(UpsertConfigurationRecordDTO $dto): Model
    {
        return $this->upsertUseCase->execute($dto);
    }

    public function delete(DeleteConfigurationRecordDTO $dto): void
    {
        $this->deleteUseCase->execute($dto);
    }
}
