<?php
namespace Modules\Inventory\Domain\Enums;
enum ProductType: string
{
    case Physical = 'physical';
    case Service = 'service';
    case Digital = 'digital';
    case Bundle = 'bundle';
    case Consumable = 'consumable';
}
