<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class BarcodeScanModel extends BaseModel
{
    protected $table = 'barcode_scans';

    protected $fillable = [
        'tenant_id', 'barcode_definition_id', 'scanned_value',
        'resolved_type', 'scanned_by_user_id', 'device_id',
        'location_tag', 'metadata', 'scanned_at',
    ];

    protected $casts = [
        'id'                    => 'int',
        'tenant_id'             => 'int',
        'barcode_definition_id' => 'int',
        'scanned_by_user_id'    => 'int',
        'metadata'              => 'array',
        'scanned_at'            => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
    ];
}
