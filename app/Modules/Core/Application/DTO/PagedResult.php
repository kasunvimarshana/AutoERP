<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTO;

use InvalidArgumentException;

final readonly class PagedResult
{
    /**
     * @param list<mixed> $items
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

        return (int) ceil($this->total / $this->perPage);
    }

    public function hasMore(): bool
    {
        return $this->page < $this->pageCount();
    }
}
