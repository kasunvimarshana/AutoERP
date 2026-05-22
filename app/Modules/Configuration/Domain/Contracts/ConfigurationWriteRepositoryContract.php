<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Domain\Aggregates\ConfigurationRecordAggregate;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

interface ConfigurationWriteRepositoryContract
{
    public function upsert(ConfigurationRecordAggregate $aggregate): Model;

    public function delete(ConfigurationRecordType $type, int $id): void;

    /**
     * @return array<string, int>
     */
    public function dependencyCounts(ConfigurationRecordType $type, int $id): array;
}
