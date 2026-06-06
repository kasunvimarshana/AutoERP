<?php

declare(strict_types=1);

namespace Modules\Supplier\Enums;

enum SupplierDocumentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Pending = 'pending';
}
