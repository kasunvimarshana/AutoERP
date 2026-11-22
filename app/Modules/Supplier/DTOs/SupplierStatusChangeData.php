<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
