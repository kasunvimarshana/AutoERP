<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use InvalidArgumentException;

final readonly class PagedResult
{
    /**
     * @param  list<mixed>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
        if ($this->total < 0) {
            throw new InvalidArgumentException('PagedResult total cannot be negative.');
        }

        if ($this->page < 1) {
            throw new InvalidArgumentException('PagedResult page must be greater than zero.');
        }

        if ($this->perPage < 1) {
            throw new InvalidArgumentException('PagedResult perPage must be greater than zero.');
        }

        if (! array_is_list($this->items)) {
            throw new InvalidArgumentException('PagedResult items must be a list.');
        }
    }

    public function pageCount(): int
    {
        if ($this->perPage <= 0) {
            return 0;
        }

        $wholePages = intdiv($this->total, $this->perPage);

        return $wholePages + ($this->total % $this->perPage === 0 ? 0 : 1);
    }

    public function hasMore(): bool
    {
        return $this->page < $this->pageCount();
    }

    /**
     * @return array{
     *     current_page: int,
     *     from: int|null,
     *     last_page: int,
     *     per_page: int,
     *     to: int|null,
     *     total: int
     * }
     */
    public function paginationMeta(): array
    {
        $offset = ($this->page - 1) * $this->perPage;
        $from = $this->total === 0 || $offset >= $this->total ? null : $offset + 1;

        return [
            'current_page' => $this->page,
            'from' => $from,
            'last_page' => max(1, $this->pageCount()),
            'per_page' => $this->perPage,
            'to' => $from === null ? null : min($this->page * $this->perPage, $this->total),
            'total' => $this->total,
        ];
    }
}
