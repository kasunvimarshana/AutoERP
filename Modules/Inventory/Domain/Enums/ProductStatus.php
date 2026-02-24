<?php
namespace Modules\Inventory\Domain\Enums;
enum ProductStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Discontinued = 'discontinued';
}
