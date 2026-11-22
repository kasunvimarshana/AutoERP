<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\User\Models\UserModel;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;

final class AgreementAdjustmentHistory extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_agreement_adjustments_history';

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('AgreementAdjustmentHistory is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('AgreementAdjustmentHistory is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'from_status' => AgreementAdjustmentStatus::class,
            'to_status' => AgreementAdjustmentStatus::class,
        ]);
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(AgreementAdjustment::class, 'adjustment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
