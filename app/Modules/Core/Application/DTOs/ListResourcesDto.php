<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTOs;

final readonly class ListResourcesDto
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $filters = [],
        public ?int $perPage = null,
        public int $page = 1,
        public ?string $sort = null,
        public ?string $include = null,
    ) {}
}
