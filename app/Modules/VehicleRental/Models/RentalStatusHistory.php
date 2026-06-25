<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalStatusHistory extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_status_histories';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','entity_id'=>'integer','changed_at'=>'datetime','metadata'=>'array']; }
}
