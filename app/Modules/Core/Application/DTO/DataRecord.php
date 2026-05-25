<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTO;

final readonly class DataRecord
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(public array $values)
    {
    }
}
