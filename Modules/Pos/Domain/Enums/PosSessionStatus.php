<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Enums;

enum PosSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Reconciled = 'reconciled';
}
