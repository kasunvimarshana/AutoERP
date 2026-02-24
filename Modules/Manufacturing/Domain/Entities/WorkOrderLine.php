<?php

namespace Modules\Manufacturing\Domain\Entities;

class WorkOrderLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenant_id,
        public readonly string $work_order_id,
        public readonly string $component_product_id,
        public readonly string $component_name,
        public readonly string $quantity_required,
        public readonly string $quantity_consumed,
        public readonly string $unit,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}
}
