<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class Gs1BarcodeModel extends BaseModel
{
    protected $table = 'gs1_barcodes';

    protected $fillable = [
        'tenant_id',
        'gs1_identifier_id',
        'barcode_type',
        'barcode_data',
        'application_identifiers',
        'is_primary',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tenant_id'        => 'integer',
        'gs1_identifier_id' => 'integer',
        'is_primary'       => 'boolean',
        'is_active'        => 'boolean',
        'metadata'         => 'array',
    ];
}
