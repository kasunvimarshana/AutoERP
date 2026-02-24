<?php

namespace Modules\HR\Domain\Enums;

enum SalaryComponentType: string
{
    case Earning   = 'earning';
    case Deduction = 'deduction';
}
