<?php

declare(strict_types=1);

namespace App\Core\DTOs;

/**
 * Pagination Data Transfer Object
 *
 * Encapsulates pagination parameters
 */
final class PaginationDTO extends BaseDTO
{
    /**
     * PaginationDTO constructor
     *
     * @param  int  $page  Current page number
     * @param  int  $perPage  Items per page
     * @param  string|null  $sortBy  Column to sort by
     * @param  string  $sortOrder  Sort direction (asc/desc)
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $sortBy = null,
        public readonly string $sortOrder = 'asc'
    ) {}

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            sortBy: $data['sort_by'] ?? null,
            sortOrder: $data['sort_order'] ?? 'asc'
        );
    }

    /**
     * Get offset for database query
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
