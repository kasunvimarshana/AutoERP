<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum TransactionStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case PARTIAL = 'partial';
    case FINAL = 'final';
    case SUSPENDED = 'suspended';
}
