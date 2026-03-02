<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTOs;

/**
 * Base Data Transfer Object.
 *
 * DTOs carry validated input data between layers without business logic.
 * All module DTOs must extend this class.
 */
abstract class DataTransferObject
{
    /**
     * Construct a DTO from a validated array of data.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Convert the DTO back to an associative array.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
