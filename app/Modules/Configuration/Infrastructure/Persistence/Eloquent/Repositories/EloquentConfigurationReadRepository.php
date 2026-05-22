<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Domain\Contracts\ConfigurationReadRepositoryContract;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

class EloquentConfigurationReadRepository implements ConfigurationReadRepositoryContract
{
    public function paginate(ConfigurationRecordType $type, int $perPage = 20): LengthAwarePaginator
    {
        return $this->newQuery($type)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function find(ConfigurationRecordType $type, int $id): ?array
    {
        /** @var Model|null $record */
        $record = $this->newQuery($type)
            ->whereKey($id)
            ->first();

        return $record?->toArray();
    }

    public function existsByUniqueField(ConfigurationRecordType $type, string $value, ?int $exceptId = null): bool
    {
        $query = $this->newQuery($type, withTrashed: true)
            ->where($type->uniqueField(), $value);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }

    private function newQuery(ConfigurationRecordType $type, bool $withTrashed = false): Builder
    {
        $modelClass = $type->modelClass();
        /** @var Builder $query */
        $query = $modelClass::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }
}
