<?php

namespace Modules\Expense\Domain\Enums;

enum ExpenseStatus: string
{
    case Draft      = 'draft';
    case Submitted  = 'submitted';
    case Approved   = 'approved';
    case Rejected   = 'rejected';
    case Reimbursed = 'reimbursed';
}
