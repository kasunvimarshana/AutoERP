<?php

namespace Modules\Manufacturing\Domain\Entities;

class WorkOrder
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $bom_id,
        public readonly string  $reference_no,
        public readonly string  $quantity_planned,
        public readonly string  $quantity_produced,
        public readonly string  $status,
        public readonly ?string $scheduled_start,
        public readonly ?string $scheduled_end,
        public readonly ?string $actual_start,
        public readonly ?string $actual_end,
        public readonly string  $created_at,
        public readonly string  $updated_at,
    ) {}
}
