<?php

namespace Modules\POS\Domain\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case DigitalWallet = 'digital_wallet';
    case Credit = 'credit';
    case Split = 'split';
}
