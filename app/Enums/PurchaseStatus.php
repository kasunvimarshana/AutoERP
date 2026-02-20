<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Ordered = 'ordered';
    case Received = 'received';
    case Partial = 'partial';
    case Cancelled = 'cancelled';
}
