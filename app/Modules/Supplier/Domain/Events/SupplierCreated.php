<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Events;

use Modules\Supplier\Domain\Entities\Supplier;

class SupplierCreated
{
    public function __construct(public readonly Supplier $supplier) {}
}
