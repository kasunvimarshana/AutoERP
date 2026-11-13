<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum AgreementAdjustmentCategory: string
{
    case CommercialRebate = 'commercial_rebate';
    case DowntimeCompensation = 'downtime_compensation';
    case DamageChargeback = 'damage_chargeback';
    case TrafficFine = 'traffic_fine';
    case Other = 'other';
}
