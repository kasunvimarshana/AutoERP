<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventorySettingModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_settings';

    protected $fillable = [
        'tenant_id', 'valuation_method', 'management_method', 'rotation_strategy',
        'allocation_algorithm', 'cycle_count_method', 'negative_stock_allowed',
        'track_lots', 'track_serial_numbers', 'track_expiry', 'auto_reorder',
        'low_stock_alert', 'metadata', 'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'negative_stock_allowed' => 'boolean',
        'track_lots' => 'boolean', 'track_serial_numbers' => 'boolean',
        'track_expiry' => 'boolean', 'auto_reorder' => 'boolean',
        'low_stock_alert' => 'boolean', 'is_active' => 'boolean', 'metadata' => 'array',
    ];
}
