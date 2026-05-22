<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reconciled = 'reconciled';
    case Voided = 'voided';
}
