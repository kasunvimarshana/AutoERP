<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Services;

final class ResourceDomainService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $allowedFields
     * @return array<string, mixed>
     */
    public function sanitizeAttributes(array $attributes, array $allowedFields = []): array
    {
        if ($allowedFields === []) {
            return $attributes;
        }

        return array_filter(
            $attributes,
            static fn (string $field): bool => in_array($field, $allowedFields, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $allowedFilters
     * @return array<string, mixed>
     */
    public function sanitizeFilters(array $filters, array $allowedFilters = []): array
    {
        if ($allowedFilters === []) {
            return $filters;
        }

        return array_filter(
            $filters,
            static fn (string $field): bool => in_array($field, $allowedFilters, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param  list<string>  $allowedSortColumns
     */
    public function sanitizeSort(?string $sort, array $allowedSortColumns = []): ?string
    {
        if ($sort === null || $sort === '') {
            return null;
        }

        $column = $sort;
        if (str_starts_with($sort, '-')) {
            $column = substr($sort, 1);
        } elseif (str_contains($sort, ':')) {
            $column = explode(':', $sort, 2)[0];
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $column) !== 1) {
            return null;
        }

        if ($allowedSortColumns !== [] && ! in_array($column, $allowedSortColumns, true)) {
            return null;
        }

        return $sort;
    }

    /**
     * @param  list<string>  $allowedIncludes
     */
    public function sanitizeInclude(?string $include, array $allowedIncludes = []): ?string
    {
        if ($include === null || $include === '') {
            return null;
        }

        $relations = array_values(array_filter(array_map('trim', explode(',', $include))));
        if ($relations === []) {
            return null;
        }

        if ($allowedIncludes !== []) {
            $relations = array_values(array_intersect($relations, $allowedIncludes));
        }

        return $relations === [] ? null : implode(',', $relations);
    }
}
