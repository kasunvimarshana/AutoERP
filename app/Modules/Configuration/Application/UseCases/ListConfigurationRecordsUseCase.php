<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Contracts\ConfigurationReadRepositoryContract;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

class ListConfigurationRecordsUseCase
{
    public function __construct(
        private readonly ConfigurationReadRepositoryContract $readRepository,
    ) {
    }

    public function execute(ConfigurationRecordType $type, int $perPage = 20): LengthAwarePaginator
    {
        return $this->readRepository->paginate($type, $perPage);
    }
}
