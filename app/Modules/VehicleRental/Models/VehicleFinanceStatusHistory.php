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
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\User\Models\UserModel;

final class VehicleFinanceStatusHistory extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_finance_status_histories';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('VehicleFinanceStatusHistory is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('VehicleFinanceStatusHistory is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'finance_agreement_id' => 'integer',
            'installment_id' => 'integer',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function financeAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleFinanceAgreement::class, 'finance_agreement_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(VehicleFinanceInstallment::class, 'installment_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'changed_by');
    }
}
