<?php

namespace App\Enums;

enum ProductType: string
{
    case Goods = 'goods';
    case Service = 'service';
    case Digital = 'digital';
    case Bundle = 'bundle';
    case Composite = 'composite';
}
