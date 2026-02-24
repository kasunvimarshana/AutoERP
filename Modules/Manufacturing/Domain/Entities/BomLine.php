<?php

namespace Modules\Manufacturing\Domain\Entities;

class BomLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenant_id,
        public readonly string $bom_id,
        public readonly string $component_product_id,
        public readonly string $component_name,
        public readonly string $quantity,
        public readonly string $unit,
        public readonly string $scrap_rate,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}
}
