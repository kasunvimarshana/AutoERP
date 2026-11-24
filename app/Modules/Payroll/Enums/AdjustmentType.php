<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Enums;

enum AdjustmentType: string
{
    case Addition = 'addition';
    case Deduction = 'deduction';
    case RetroPay = 'retro_pay';
    case Reimbursement = 'reimbursement';
    case Correction = 'correction';
}
