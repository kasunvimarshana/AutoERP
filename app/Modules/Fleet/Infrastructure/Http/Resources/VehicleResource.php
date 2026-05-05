<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                               => $this->id,
            'tenant_id'                        => $this->tenant_id,
            'vehicle_type_id'                  => $this->vehicle_type_id,
            'registration_number'              => $this->registration_number,
            'make'                             => $this->make,
            'model'                            => $this->model,
            'year'                             => $this->year,
            'color'                            => $this->color,
            'vin_number'                       => $this->vin_number,
            'engine_number'                    => $this->engine_number,
            'ownership_type'                   => $this->ownership_type,
            'owner_supplier_id'                => $this->owner_supplier_id,
            'owner_commission_pct'             => $this->owner_commission_pct,
            'is_rentable'                      => $this->is_rentable,
            'is_serviceable'                   => $this->is_serviceable,
            'current_state'                    => $this->current_state,
            'current_odometer'                 => $this->current_odometer,
            'fuel_type'                        => $this->fuel_type,
            'fuel_capacity'                    => $this->fuel_capacity,
            'seating_capacity'                 => $this->seating_capacity,
            'transmission'                     => $this->transmission,
            'asset_account_id'                 => $this->asset_account_id,
            'accum_depreciation_account_id'    => $this->accum_depreciation_account_id,
            'depreciation_expense_account_id'  => $this->depreciation_expense_account_id,
            'rental_revenue_account_id'        => $this->rental_revenue_account_id,
            'service_revenue_account_id'       => $this->service_revenue_account_id,
            'acquisition_cost'                 => $this->acquisition_cost,
            'acquired_at'                      => $this->acquired_at,
            'is_active'                        => $this->is_active,
            'created_at'                       => $this->created_at,
            'updated_at'                       => $this->updated_at,
        ];
    }
}
