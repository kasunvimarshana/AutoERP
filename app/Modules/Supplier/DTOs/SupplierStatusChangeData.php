<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

use Modules\Supplier\Enums\SupplierStatus;

final readonly class SupplierStatusChangeData
{
    public function __construct(
        public SupplierStatus $newStatus,
        public ?string $reason = null,
        public ?int $changedBy = null,
    ) {}
}
