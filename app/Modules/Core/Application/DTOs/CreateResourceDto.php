<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTOs;

final readonly class CreateResourceDto
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public array $attributes,
    ) {}
}
