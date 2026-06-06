<?php

declare(strict_types=1);

namespace Modules\Supplier\Enums;

enum SupplierType: string
{
    case Company = 'company';
    case Individual = 'individual';
    case Government = 'government';
    case Internal = 'internal';
    case Foreign = 'foreign';
    case Other = 'other';
}
