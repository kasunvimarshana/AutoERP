<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'asset_code' => $this->getAssetCode(),
            'asset_name' => $this->getAssetName(),
            'usage_mode' => $this->getUsageMode(),
            'lifecycle_status' => $this->getLifecycleStatus(),
            'rental_status' => $this->getRentalStatus(),
            'service_status' => $this->getServiceStatus(),
            'product_id' => $this->getProductId(),
            'serial_id' => $this->getSerialId(),
            'supplier_id' => $this->getSupplierId(),
            'warehouse_id' => $this->getWarehouseId(),
            'currency_id' => $this->getCurrencyId(),
            'registration_number' => $this->getRegistrationNumber(),
            'chassis_number' => $this->getChassisNumber(),
            'engine_number' => $this->getEngineNumber(),
            'year_of_manufacture' => $this->getYearOfManufacture(),
            'make' => $this->getMake(),
            'model' => $this->getModel(),
            'color' => $this->getColor(),
            'fuel_type' => $this->getFuelType(),
            'purchase_cost' => $this->getPurchaseCost(),
            'book_value' => $this->getBookValue(),
            'purchase_date' => $this->getPurchaseDate()?->format('Y-m-d'),
            'current_odometer' => $this->getCurrentOdometer(),
            'engine_hours' => $this->getEngineHours(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
