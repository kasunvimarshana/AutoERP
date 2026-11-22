<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\UOM\DTOs;

final readonly class CreateUomData
{
    public function __construct(public array $payload) {}

    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }
}
