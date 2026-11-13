<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BillingCycleStatus: string
{
    case Draft = 'draft';
    case Stage1Generated = 'stage1_generated';
    case Stage2Attached = 'stage2_attached';
    case Finalized = 'finalized';
    case Cancelled = 'cancelled';
}
