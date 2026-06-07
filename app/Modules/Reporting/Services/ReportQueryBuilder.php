<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use BackedEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\DTOs\ReportFilter;

final class ReportQueryBuilder
{
    /**
     * @param array<string, mixed> $input
     * @return Builder<Model>
     */
    public function query(ReportDefinition $definition, int $tenantId, ?int $organizationUnitId, array $input): Builder
    {
        /** @var Builder<Model> $query */
        $query = $definition->model::query();
        $model = $query->getModel();

        if ($definition->relations !== []) {
            $query->with($definition->relations);
        }

        if ($this->hasColumn($model, 'tenant_id')) {
            $query->where($model->getTable().'.tenant_id', $tenantId);
        }

        if ($this->hasColumn($model, 'organization_unit_id')) {
            $qualified = $model->getTable().'.organization_unit_id';
            if ($organizationUnitId === null) {
                $query->whereNull($qualified);
            } elseif ($definition->includeGlobalOrganization) {
                $query->where(fn (Builder $scope): Builder => $scope->whereNull($qualified)->orWhere($qualified, $organizationUnitId));
            } else {
                $query->where($qualified, $organizationUnitId);
            }
        }

        foreach ($definition->constraints as $field => $value) {
            $query->where($field, $value);
        }

        if ($definition->scope !== null) {
            $query = ($definition->scope)($query);
        }

        $this->applySearch($query, (string) ($input['search'] ?? ''), $definition->search);
        $this->applyDateRange($query, $definition, $input);
        $this->applyFilters($query, $definition, (array) ($input['filters'] ?? []));
        $this->applySort($query, $definition, $input);

        return $query;
    }

    /**
     * @param array<string, mixed> $input
     * @return LengthAwarePaginator<Model>
     */
    public function paginate(ReportDefinition $definition, int $tenantId, ?int $organizationUnitId, array $input, int $perPage): LengthAwarePaginator
    {
        return $this->query($definition, $tenantId, $organizationUnitId, $input)->paginate($perPage);
    }

    /**
     * @param iterable<Model|array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public function rows(ReportDefinition $definition, iterable $rows): array
    {
        $formatted = [];

        foreach ($rows as $row) {
            $formatted[] = $this->row($definition, $row);
        }

        return $formatted;
    }

    /**
     * @param Model|array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function row(ReportDefinition $definition, Model|array $row): array
    {
        $values = [];

        foreach ($definition->columns as $column) {
            $values[$column->key] = $this->format($this->value($row, $column), $column->format);
        }

        return $values;
    }

    /**
     * @param array<int, string> $fields
     */
    private function applySearch(Builder $query, string $search, array $fields): void
    {
        $search = trim($search);
        if ($search === '' || $fields === []) {
            return;
        }

        $query->where(function (Builder $scope) use ($fields, $search): void {
            foreach ($fields as $field) {
                $this->whereLike($scope, $field, $search, 'or');
            }
        });
    }

    /**
     * @param array<string, mixed> $input
     */
    private function applyDateRange(Builder $query, ReportDefinition $definition, array $input): void
    {
        if ($definition->dateColumn === null) {
            return;
        }

        if (! empty($input['date_from'])) {
            $query->whereDate($definition->dateColumn, '>=', (string) $input['date_from']);
        }

        if (! empty($input['date_to'])) {
            $query->whereDate($definition->dateColumn, '<=', (string) $input['date_to']);
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function applyFilters(Builder $query, ReportDefinition $definition, array $values): void
    {
        foreach ($values as $key => $value) {
            $filter = $definition->filter((string) $key);
            if (! $filter || $value === null || $value === '') {
                continue;
            }

            $this->applyFilter($query, $filter, $value);
        }
    }

    private function applyFilter(Builder $query, ReportFilter $filter, mixed $value): void
    {
        if ($filter->operator === 'like') {
            $this->whereLike($query, $filter->field, (string) $value, 'and');

            return;
        }

        if (str_contains($filter->field, '.')) {
            [$relation, $field] = explode('.', $filter->field, 2);
            $query->whereHas($relation, fn (Builder $scope): Builder => $scope->where($field, $filter->operator, $value));

            return;
        }

        $query->where($filter->field, $filter->operator, $value);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function applySort(Builder $query, ReportDefinition $definition, array $input): void
    {
        $sort = (string) ($input['sort'] ?? $definition->defaultSort);
        $direction = (string) ($input['direction'] ?? $definition->defaultDirection);
        $direction = $direction === 'asc' ? 'asc' : 'desc';
        $column = $definition->column($sort);
        $sortBy = $column?->sortBy ?? $definition->defaultSort;

        if (! str_contains($sortBy, '.')) {
            $sortBy = $query->getModel()->getTable().'.'.$sortBy;
        }

        $query->orderBy($sortBy, $direction);
    }

    private function whereLike(Builder $query, string $field, string $search, string $boolean): void
    {
        if (str_contains($field, '.')) {
            [$relation, $column] = explode('.', $field, 2);
            $method = $boolean === 'or' ? 'orWhereHas' : 'whereHas';
            $query->{$method}($relation, fn (Builder $scope): Builder => $scope->where($column, 'like', '%'.$search.'%'));

            return;
        }

        $method = $boolean === 'or' ? 'orWhere' : 'where';
        $query->{$method}($field, 'like', '%'.$search.'%');
    }

    private function hasColumn(Model $model, string $column): bool
    {
        return Schema::hasColumn($model->getTable(), $column);
    }

    private function value(Model|array $row, ReportColumn $column): mixed
    {
        $path = $column->path ?? $column->key;
        $value = data_get($row, $path);

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    private function format(mixed $value, string $format): mixed
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value === null) {
            return null;
        }

        if ($format === 'boolean') {
            return (bool) $value;
        }

        if ($format === 'date' && method_exists($value, 'toDateString')) {
            return $value->toDateString();
        }

        if ($format === 'datetime' && method_exists($value, 'toDateTimeString')) {
            return $value->toDateTimeString();
        }

        return $value;
    }
}
