<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

interface ConfigurationReadRepositoryContract
{
    public function paginate(ConfigurationRecordType $type, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return array<string, mixed>|null
     */
    public function find(ConfigurationRecordType $type, int $id): ?array;

    public function existsByUniqueField(ConfigurationRecordType $type, string $value, ?int $exceptId = null): bool;
}
