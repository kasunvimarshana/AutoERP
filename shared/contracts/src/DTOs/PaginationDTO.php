<?php

declare(strict_types=1);

namespace KvSaas\Contracts\DTOs;

/**
 * PaginationDTO
 *
 * Encapsulates all dynamic pagination parameters, ensuring no hardcoded
 * values anywhere in the system.
 */
final class PaginationDTO
{
    public function __construct(
        public readonly int    $page     = 1,
        public readonly int    $perPage  = 15,
        public readonly array  $columns  = ['*'],
        public readonly string $pageName = 'page',
    ) {}

    /**
     * Create a PaginationDTO from an array of request data.
     *
     * @param  array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            page:     (int) ($data['page']      ?? 1),
            perPage:  (int) ($data['per_page']  ?? 15),
            columns:  (array) ($data['columns'] ?? ['*']),
            pageName: (string) ($data['page_name'] ?? 'page'),
        );
    }

    /**
     * Convert back to a plain array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page'      => $this->page,
            'per_page'  => $this->perPage,
            'columns'   => $this->columns,
            'page_name' => $this->pageName,
        ];
    }
}
