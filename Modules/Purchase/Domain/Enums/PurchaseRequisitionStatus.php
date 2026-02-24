<?php

namespace Modules\Purchase\Domain\Enums;

enum PurchaseRequisitionStatus: string
{
    case Draft           = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved        = 'approved';
    case Rejected        = 'rejected';
    case PoRaised        = 'po_raised';
    case Closed          = 'closed';
}
