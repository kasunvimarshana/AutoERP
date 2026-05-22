<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Modules\Configuration\Application\DTOs\UpsertConfigurationRecordDTO;
use Modules\Configuration\Domain\Aggregates\ConfigurationRecordAggregate;
use Modules\Configuration\Domain\Contracts\ConfigurationWriteRepositoryContract;
use Modules\Configuration\Domain\Events\ConfigurationRecordChanged;
use Modules\Configuration\Domain\Services\ConfigurationDomainService;

class UpsertConfigurationRecordUseCase
{
    public function __construct(
        private readonly ConfigurationDomainService $domainService,
        private readonly ConfigurationWriteRepositoryContract $writeRepository,
    ) {
    }

    public function execute(UpsertConfigurationRecordDTO $dto): Model
    {
        $aggregate = ConfigurationRecordAggregate::fromDTO($dto);
        $action = $aggregate->id() === null ? 'created' : 'updated';

        $this->domainService->assertRecordCanBeSaved($aggregate);

        $record = $this->writeRepository->upsert($aggregate);

        Event::dispatch(new ConfigurationRecordChanged(
            type: $aggregate->type(),
            id: (int) $record->getKey(),
            action: $action,
        ));

        return $record;
    }
}
