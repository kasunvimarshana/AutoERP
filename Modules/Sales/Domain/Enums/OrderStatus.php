<?php
namespace Modules\Sales\Domain\Enums;
enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case PartiallyShipped = 'partially_shipped';
    case Shipped = 'shipped';
    case Invoiced = 'invoiced';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
