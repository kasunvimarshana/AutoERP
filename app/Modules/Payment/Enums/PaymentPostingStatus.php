<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentPostingStatus: string
{
    case NotPosted = 'not_posted';
    case Posting = 'posting';
    case Posted = 'posted';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
