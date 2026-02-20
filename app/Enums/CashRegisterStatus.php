<?php

namespace App\Enums;

enum CashRegisterStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
