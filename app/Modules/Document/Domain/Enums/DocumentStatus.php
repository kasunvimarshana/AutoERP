<?php

namespace Modules\Document\Domain\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Void = 'void';
    case Archived = 'archived';
}
