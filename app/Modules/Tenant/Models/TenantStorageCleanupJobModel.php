<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\TenantOwnedModel;

final class TenantStorageCleanupJobModel extends TenantOwnedModel
{
    use HasImmutableTenantOwnership;

    protected $table = 'tenant_storage_cleanup_jobs';

    protected $fillable = [
        'tenant_id',
        'storage_disk',
        'storage_path',
        'reason',
        'status',
        'attempts',
        'last_error',
        'next_attempt_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'attempts' => 'integer',
            'next_attempt_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }
}
