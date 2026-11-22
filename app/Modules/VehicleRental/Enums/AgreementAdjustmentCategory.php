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

enum AgreementAdjustmentCategory: string
{
    case CommercialRebate = 'commercial_rebate';
    case DowntimeCompensation = 'downtime_compensation';
    case DamageChargeback = 'damage_chargeback';
    case TrafficFine = 'traffic_fine';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CommercialRebate => 'Commercial Rebate',
            self::DowntimeCompensation => 'Downtime Compensation',
            self::DamageChargeback => 'Damage Chargeback',
            self::TrafficFine => 'Traffic Fine',
            self::Other => 'Other Adjustment',
        };
    }
}
