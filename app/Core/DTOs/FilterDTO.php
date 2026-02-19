<?php

declare(strict_types=1);

namespace App\Core\DTOs;

/**
 * Filter Data Transfer Object
 *
 * Encapsulates filtering parameters for queries
 */
final class FilterDTO extends BaseDTO
{
    /**
     * FilterDTO constructor
     *
     * @param  array<string, mixed>  $filters  Key-value pairs for filtering
     * @param  array<string>  $relations  Relations to eager load
     * @param  array<string>  $columns  Columns to select
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly array $relations = [],
        public readonly array $columns = ['*']
    ) {}

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'filters' => $this->filters,
            'relations' => $this->relations,
            'columns' => $this->columns,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            filters: $data['filters'] ?? [],
            relations: $data['relations'] ?? [],
            columns: $data['columns'] ?? ['*']
        );
    }

    /**
     * Check if a filter exists
     */
    public function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    /**
     * Get a filter value
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }
}
