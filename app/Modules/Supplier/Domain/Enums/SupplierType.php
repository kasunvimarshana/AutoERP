<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Enums;

enum SupplierType: string
{
    case Individual = 'individual';
    case Company = 'company';
}
