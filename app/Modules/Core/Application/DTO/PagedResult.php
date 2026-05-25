<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTO;

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
