<?php

namespace App\Enums;

enum StockAdjustmentReason: string
{
    case Damage = 'damage';
    case Theft = 'theft';
    case Expiry = 'expiry';
    case Correction = 'correction';
    case Audit = 'audit';
    case Other = 'other';
}
