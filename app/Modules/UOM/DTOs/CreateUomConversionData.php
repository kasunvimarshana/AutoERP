<?php

declare(strict_types=1);

namespace Modules\UOM\DTOs;

final readonly class CreateUomConversionData
{
    public function __construct(public array $payload) {}

    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }
}
