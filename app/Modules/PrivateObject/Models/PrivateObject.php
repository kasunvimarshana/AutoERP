<?php

declare(strict_types=1);

namespace Modules\PrivateObject\Models;

use Modules\Core\Models\TenantOwnedModel;
use Modules\PrivateObject\Enums\PrivateObjectScanStatus;

final class PrivateObject extends TenantOwnedModel
{
    protected $table = 'private_objects';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'size_bytes' => 'integer',
            'scan_status' => PrivateObjectScanStatus::class,
            'scanned_at' => 'immutable_datetime',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}
