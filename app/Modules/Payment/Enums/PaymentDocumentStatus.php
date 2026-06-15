<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentDocumentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Voided = 'voided';
    case Reversed = 'reversed';
}
