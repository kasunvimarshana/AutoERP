<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\ConfigurationModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentConfigurationRepository extends EloquentRepository implements ConfigurationRepositoryInterface
{
    public function __construct(ConfigurationModel $model)
    {
        parent::__construct($model, ConfigurationModel::COLUMN_ID);
    }

    public function findByKey(string $key): ?DataRecord
    {
        $model = $this->query()->where(ConfigurationModel::COLUMN_KEY, $key)->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    public function pageByFilters(?string $prefix, ?string $source, int $perPage, int $page): PagedResult
    {
        $resolvedPage = $page > 0 ? $page : 1;
        $resolvedPerPage = $perPage > 0 ? $perPage : 1;

        $paginator = $this->applyFilters($this->query(), $prefix, $source)
            ->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function deleteByKey(string $key): bool
    {
        $model = $this->query()->where(ConfigurationModel::COLUMN_KEY, $key)->first();

        if (! $model instanceof Model) {
            return false;
        }

        return (bool) $model->delete();
    }

    private function applyFilters(Builder $query, ?string $prefix, ?string $source): Builder
    {
        if ($prefix !== null && $prefix !== '') {
            $query->where(ConfigurationModel::COLUMN_KEY, 'like', $prefix . '%');
        }

        if ($source !== null && $source !== '') {
            $query->where(ConfigurationModel::COLUMN_SOURCE, $source);
        }

        return $query;
    }
}
