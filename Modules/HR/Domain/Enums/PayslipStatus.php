<?php

namespace Modules\HR\Domain\Enums;

enum PayslipStatus: string
{
    case PENDING = 'pending';
    case PAID    = 'paid';
}
