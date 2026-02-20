<?php

namespace App\Enums;

enum PosTransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Void = 'void';
    case Refunded = 'refunded';
}
