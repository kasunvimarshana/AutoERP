<?php

declare(strict_types=1);

namespace Modules\Vehicle\Data;

use Modules\Vehicle\Enums\VehicleOwnerType;

final readonly class VehicleOwnerSnapshot
{
    public function __construct(
        public VehicleOwnerType $type,
        public ?int $id,
        public string $key,
        public string $code,
        public string $name,
    ) {}
}
