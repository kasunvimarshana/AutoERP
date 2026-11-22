<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum BillingCycleStatus: string
{
    case Draft = 'draft';
    case Stage1Generated = 'stage1_generated';
    case Stage2Attached = 'stage2_attached';
    case Finalized = 'finalized';
    case Cancelled = 'cancelled';
}
