<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Models;

use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final class VehicleServiceSupervisorCommissionPolicy extends TenantOwnedModel
{
    protected $table = 'vehicle_service_supervisor_commission_policies';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'commission_type' => VehicleServiceCommissionType::class,
            'commission_value' => 'decimal:6',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
