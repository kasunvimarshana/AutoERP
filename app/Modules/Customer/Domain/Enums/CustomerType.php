<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Company = 'company';
}
