<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTOs;

final readonly class UpdateResourceDto
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int|string $id,
        public array $attributes,
    ) {}
}
