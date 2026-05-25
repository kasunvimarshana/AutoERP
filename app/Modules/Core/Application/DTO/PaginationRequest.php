<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTO;

final readonly class PaginationRequest
{
    public function __construct(
        public int $page,
        public int $perPage,
    ) {
    }
}
