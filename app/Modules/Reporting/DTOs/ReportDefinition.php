<?php

declare(strict_types=1);

namespace Modules\Reporting\DTOs;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class ReportDefinition
{
    /**
     * @param class-string<Model> $model
     * @param array<int, ReportColumn> $columns
     * @param array<int, string> $search
     * @param array<int, string> $relations
     * @param array<int, ReportFilter> $filters
     * @param array<string, mixed> $constraints
     * @param Closure(Builder): Builder|null $scope
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $group,
        public string $model,
        public array $columns,
        public array $search = [],
        public array $relations = [],
        public array $filters = [],
        public ?string $dateColumn = null,
        public string $defaultSort = 'id',
        public string $defaultDirection = 'desc',
        public bool $includeGlobalOrganization = false,
        public array $constraints = [],
        public ?Closure $scope = null,
        public string $description = '',
    ) {}

    public function column(string $key): ?ReportColumn
    {
        foreach ($this->columns as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }

        return null;
    }

    public function filter(string $key): ?ReportFilter
    {
        foreach ($this->filters as $filter) {
            if ($filter->key === $key) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'group' => $this->group,
            'description' => $this->description,
            'columns' => array_map(fn (ReportColumn $column): array => $column->toArray(), $this->columns),
            'filters' => array_map(fn (ReportFilter $filter): array => $filter->toArray(), $this->filters),
            'supports_date_range' => $this->dateColumn !== null,
            'default_sort' => $this->defaultSort,
            'default_direction' => $this->defaultDirection,
        ];
    }
}
