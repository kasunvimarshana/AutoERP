<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\User\Models\UserModel;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;

final class AgreementAdjustmentHistory extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_agreement_adjustments_history';

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
