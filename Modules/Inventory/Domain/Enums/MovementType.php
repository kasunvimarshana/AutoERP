<?php
namespace Modules\Inventory\Domain\Enums;
enum MovementType: string
{
    case Receipt = 'receipt';
    case Delivery = 'delivery';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case Scrap = 'scrap';
}
