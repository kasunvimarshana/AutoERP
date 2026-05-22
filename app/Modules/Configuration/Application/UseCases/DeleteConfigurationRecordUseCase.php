<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Illuminate\Support\Facades\Event;
use Modules\Configuration\Application\DTOs\DeleteConfigurationRecordDTO;
use Modules\Configuration\Domain\Contracts\ConfigurationWriteRepositoryContract;
use Modules\Configuration\Domain\Events\ConfigurationRecordChanged;
use Modules\Configuration\Domain\Services\ConfigurationDomainService;

class DeleteConfigurationRecordUseCase
{
    public function __construct(
        private readonly ConfigurationDomainService $domainService,
        private readonly ConfigurationWriteRepositoryContract $writeRepository,
    ) {
    }

    public function execute(DeleteConfigurationRecordDTO $dto): void
    {
        $this->domainService->assertRecordCanBeDeleted($dto->type, $dto->id);
        $this->writeRepository->delete($dto->type, $dto->id);

        Event::dispatch(new ConfigurationRecordChanged(
            type: $dto->type,
            id: $dto->id,
            action: 'deleted',
        ));
    }
}
