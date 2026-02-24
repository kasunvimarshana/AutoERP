<?php

namespace Modules\POS\Domain\Enums;

enum PosSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Reconciled = 'reconciled';
}
