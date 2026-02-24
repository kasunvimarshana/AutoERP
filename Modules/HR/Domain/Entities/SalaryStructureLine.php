<?php

namespace Modules\HR\Domain\Entities;

class SalaryStructureLine
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $structure_id,
        public readonly string  $component_id,
        public readonly int     $sequence,
        public readonly ?string $override_amount,
    ) {}
}
