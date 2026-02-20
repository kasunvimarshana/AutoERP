<?php

namespace App\Enums;

enum PaymentAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Card = 'card';
    case MobileMoney = 'mobile_money';
    case Credit = 'credit';
    case Other = 'other';
}
